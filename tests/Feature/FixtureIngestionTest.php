<?php

namespace Tests\Feature;

use App\Contracts\FixtureProvider;
use App\Jobs\FixtureIngestionJob;
use App\Jobs\ResultIngestionJob;
use App\Models\GameMatch;
use App\Models\Setting;
use App\Services\Fixtures\FootballDataProvider;
use App\Support\MarketRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FixtureIngestionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('prediction_provider', 'poisson_xg');
        Setting::set('football_data_token', 'test-token');
        $this->app->bind(FixtureProvider::class, fn () => new FootballDataProvider);
    }

    protected function fakeFootballData(array $matches, array $standings = []): void
    {
        Http::fake([
            '*/competitions/*/standings' => Http::response(['standings' => $standings]),
            '*/matches*' => Http::response(['matches' => $matches]),
        ]);
    }

    public function test_fixtures_are_keyed_on_the_provider_id_not_team_names(): void
    {
        // The same two teams meeting twice in a season — the old dedupe on
        // (home, away, league) collapsed these into a single row.
        $this->fakeFootballData([
            [
                'id' => 1001,
                'utcDate' => now()->addDays(2)->toIso8601String(),
                'status' => 'SCHEDULED',
                'competition' => ['name' => 'Premier League', 'code' => 'PL'],
                'homeTeam' => ['name' => 'Arsenal'],
                'awayTeam' => ['name' => 'Chelsea'],
            ],
            [
                'id' => 1002,
                'utcDate' => now()->addDays(5)->toIso8601String(),
                'status' => 'SCHEDULED',
                'competition' => ['name' => 'Premier League', 'code' => 'PL'],
                'homeTeam' => ['name' => 'Arsenal'],
                'awayTeam' => ['name' => 'Chelsea'],
            ],
        ]);

        app(FixtureIngestionJob::class)->handle(
            app(FixtureProvider::class),
            app(\App\Services\PredictionService::class),
            app(\App\Services\PreviewGenerationService::class),
        );

        $this->assertDatabaseCount('matches', 2);
        $this->assertDatabaseHas('matches', ['external_id' => '1001']);
        $this->assertDatabaseHas('matches', ['external_id' => '1002']);
    }

    public function test_re_running_ingestion_updates_rather_than_duplicates(): void
    {
        $payload = [[
            'id' => 2001,
            'utcDate' => now()->addDays(2)->toIso8601String(),
            'status' => 'SCHEDULED',
            'competition' => ['name' => 'Premier League', 'code' => 'PL'],
            'homeTeam' => ['name' => 'Arsenal'],
            'awayTeam' => ['name' => 'Chelsea'],
        ]];

        $this->fakeFootballData($payload);

        $run = fn () => app(FixtureIngestionJob::class)->handle(
            app(FixtureProvider::class),
            app(\App\Services\PredictionService::class),
            app(\App\Services\PreviewGenerationService::class),
        );

        $run();
        $run();

        $this->assertDatabaseCount('matches', 1);
        // One row per market this fixture supports, not two runs' worth. The
        // clubs have no corner or card rates, so the count markets are absent —
        // which is the gate working, not a missing prediction.
        $this->assertDatabaseCount('predictions', count(MarketRegistry::generatedFor(false)));
    }

    public function test_team_form_is_derived_from_the_standings_table(): void
    {
        $this->fakeFootballData(
            [[
                'id' => 3001,
                'utcDate' => now()->addDays(2)->toIso8601String(),
                'status' => 'SCHEDULED',
                'competition' => ['name' => 'Premier League', 'code' => 'PL'],
                'homeTeam' => ['name' => 'Arsenal'],
                'awayTeam' => ['name' => 'Chelsea'],
            ]],
            [[
                'type' => 'TOTAL',
                'table' => [
                    ['team' => ['name' => 'Arsenal'], 'playedGames' => 10, 'goalsFor' => 25, 'goalsAgainst' => 8],
                    ['team' => ['name' => 'Chelsea'], 'playedGames' => 10, 'goalsFor' => 12, 'goalsAgainst' => 15],
                ],
            ]],
        );

        app(FixtureIngestionJob::class)->handle(
            app(FixtureProvider::class),
            app(\App\Services\PredictionService::class),
            app(\App\Services\PreviewGenerationService::class),
        );

        $match = GameMatch::where('external_id', '3001')->firstOrFail();

        $this->assertSame(2.5, $match->home_form['gf']);
        $this->assertSame(0.8, $match->home_form['ga']);
        $this->assertSame(1.2, $match->away_form['gf']);
    }

    public function test_results_are_settled_automatically(): void
    {
        $match = GameMatch::create([
            'external_id' => '4001',
            'home_team' => 'Arsenal',
            'away_team' => 'Chelsea',
            'league' => 'Premier League',
            'kickoff_at' => now()->subDay(),
        ]);

        Http::fake(['*/matches*' => Http::response(['matches' => [[
            'id' => 4001,
            'score' => ['fullTime' => ['home' => 2, 'away' => 1]],
        ]]])]);

        app(ResultIngestionJob::class)->handle(
            app(FixtureProvider::class),
            app(\App\Services\TrackRecordService::class),
        );

        $this->assertDatabaseHas('results', [
            'match_id' => $match->id,
            'home_score' => 2,
            'away_score' => 1,
        ]);
        $this->assertSame('finished', $match->fresh()->status);
    }

    public function test_a_finished_match_with_no_score_is_skipped(): void
    {
        GameMatch::create([
            'external_id' => '5001',
            'home_team' => 'Arsenal',
            'away_team' => 'Chelsea',
            'league' => 'Premier League',
            'kickoff_at' => now()->subDay(),
        ]);

        Http::fake(['*/matches*' => Http::response(['matches' => [[
            'id' => 5001,
            'score' => ['fullTime' => ['home' => null, 'away' => null]],
        ]]])]);

        app(ResultIngestionJob::class)->handle(
            app(FixtureProvider::class),
            app(\App\Services\TrackRecordService::class),
        );

        // A missing score is a data error, not a 0-0.
        $this->assertDatabaseCount('results', 0);
    }

    public function test_ingestion_is_skipped_when_the_provider_is_unconfigured(): void
    {
        Setting::set('football_data_token', '');
        Http::fake();

        app(FixtureIngestionJob::class)->handle(
            app(FixtureProvider::class),
            app(\App\Services\PredictionService::class),
            app(\App\Services\PreviewGenerationService::class),
        );

        $this->assertDatabaseCount('matches', 0);
        Http::assertNothingSent();
    }
}
