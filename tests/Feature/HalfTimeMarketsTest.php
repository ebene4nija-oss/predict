<?php

namespace Tests\Feature;

use App\Contracts\FixtureProvider;
use App\Jobs\ResultIngestionJob;
use App\Models\GameMatch;
use App\Models\Prediction;
use App\Models\Result;
use App\Models\Setting;
use App\Models\User;
use App\Services\Fixtures\FootballDataProvider;
use App\Services\TrackRecordService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The first-half markets: ingesting the half-time score, settling from it, and
 * the rule that a fixture without one leaves them ungraded rather than lost.
 */
class HalfTimeMarketsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('prediction_provider', 'poisson_xg');
        Setting::set('football_data_token', 'test-token');
        $this->app->bind(FixtureProvider::class, fn () => new FootballDataProvider);
    }

    protected function finishedMatch(string $externalId = '5001'): GameMatch
    {
        return GameMatch::create([
            'external_id' => $externalId,
            'home_team' => 'Half Time FC',
            'away_team' => 'Break United',
            'league' => 'Premier League',
            'kickoff_at' => now()->subDay(),
        ]);
    }

    public function test_the_half_time_score_is_ingested_alongside_the_full_time_one(): void
    {
        $match = $this->finishedMatch();

        Http::fake(['*/matches*' => Http::response(['matches' => [[
            'id' => 5001,
            'score' => [
                'fullTime' => ['home' => 3, 'away' => 1],
                'halfTime' => ['home' => 1, 'away' => 1],
            ],
        ]]])]);

        app(ResultIngestionJob::class)->handle(
            app(FixtureProvider::class),
            app(TrackRecordService::class),
        );

        $this->assertDatabaseHas('results', [
            'match_id' => $match->id,
            'home_score' => 3,
            'away_score' => 1,
            'ht_home_score' => 1,
            'ht_away_score' => 1,
        ]);
    }

    /**
     * football-data omits halfTime on some fixtures. Null, never 0-0: a
     * defaulted zero would settle every one of them as a goalless first half.
     */
    public function test_a_fixture_without_a_half_time_score_stores_null(): void
    {
        $match = $this->finishedMatch();

        Http::fake(['*/matches*' => Http::response(['matches' => [[
            'id' => 5001,
            'score' => ['fullTime' => ['home' => 2, 'away' => 0]],
        ]]])]);

        app(ResultIngestionJob::class)->handle(
            app(FixtureProvider::class),
            app(TrackRecordService::class),
        );

        $result = Result::where('match_id', $match->id)->firstOrFail();

        $this->assertNull($result->ht_home_score);
        $this->assertNull($result->ht_away_score);
        $this->assertSame([], $result->gradingContext());
    }

    /**
     * A half-time score higher than the full-time one is a provider error. It
     * is dropped rather than stored, because it would settle the first-half
     * markets against a scoreline that cannot have happened.
     */
    public function test_an_impossible_half_time_score_is_rejected(): void
    {
        $match = $this->finishedMatch();

        Http::fake(['*/matches*' => Http::response(['matches' => [[
            'id' => 5001,
            'score' => [
                'fullTime' => ['home' => 1, 'away' => 0],
                'halfTime' => ['home' => 2, 'away' => 0],
            ],
        ]]])]);

        app(ResultIngestionJob::class)->handle(
            app(FixtureProvider::class),
            app(TrackRecordService::class),
        );

        $result = Result::where('match_id', $match->id)->firstOrFail();

        $this->assertSame(1, $result->home_score);
        $this->assertNull($result->ht_home_score);
    }

    public function test_the_track_record_grades_half_time_picks_only_where_the_score_exists(): void
    {
        // Graded: 1-0 at the break, so the first-half over lands.
        $withHalfTime = $this->finishedMatch('6001');
        Prediction::create([
            'match_id' => $withHalfTime->id,
            'market' => 'fh_over_0_5',
            'pick' => '1H Over 0.5',
            'probability' => 0.74,
            'published_at' => $withHalfTime->kickoff_at->copy()->subHour(),
        ]);
        Result::create([
            'match_id' => $withHalfTime->id,
            'home_score' => 2,
            'away_score' => 0,
            'ht_home_score' => 1,
            'ht_away_score' => 0,
            'settled_at' => now(),
        ]);

        // Not graded: same pick, no half-time score recorded.
        $withoutHalfTime = $this->finishedMatch('6002');
        Prediction::create([
            'match_id' => $withoutHalfTime->id,
            'market' => 'fh_over_0_5',
            'pick' => '1H Over 0.5',
            'probability' => 0.74,
            'published_at' => $withoutHalfTime->kickoff_at->copy()->subHour(),
        ]);
        Result::create([
            'match_id' => $withoutHalfTime->id,
            'home_score' => 2,
            'away_score' => 0,
            'settled_at' => now(),
        ]);

        $stats = app(TrackRecordService::class)->getAccuracyStats();

        $this->assertSame(1, $stats['ai']['fh_over_0_5']['total'], 'only the settleable fixture counts');
        $this->assertSame(1, $stats['ai']['fh_over_0_5']['won']);
    }

    public function test_an_admin_can_settle_a_half_time_score_and_it_grades_the_markets(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $match = $this->finishedMatch('7001');

        $response = $this->actingAs($admin)->post("/admin/matches/{$match->id}/settle", [
            'home_score' => 3,
            'away_score' => 1,
            'ht_home_score' => 0,
            'ht_away_score' => 1,
        ]);

        $response->assertRedirect();

        $result = Result::where('match_id', $match->id)->firstOrFail();

        $this->assertSame(0, $result->ht_home_score);
        $this->assertSame(1, $result->ht_away_score);
        $this->assertSame('HT Away Win', $result->actual_outcome['ht_win']);
        $this->assertSame('1H Over 0.5', $result->actual_outcome['fh_over_0_5']);
        // Won 3-1 but trailing at the break: the two readings must disagree.
        $this->assertSame('Home Win', $result->actual_outcome['win']);
    }

    public function test_a_half_time_score_above_the_full_time_score_is_refused(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $match = $this->finishedMatch('7002');

        $response = $this->actingAs($admin)->post("/admin/matches/{$match->id}/settle", [
            'home_score' => 1,
            'away_score' => 0,
            'ht_home_score' => 2,
            'ht_away_score' => 0,
        ]);

        $response->assertSessionHasErrors('ht_home_score');
        $this->assertDatabaseCount('results', 0);
    }

    /**
     * Half of a half-time score settles nothing, so it must not be stored as if
     * the missing side finished the half goalless.
     */
    public function test_a_partial_half_time_score_is_stored_as_absent(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $match = $this->finishedMatch('7003');

        $this->actingAs($admin)->post("/admin/matches/{$match->id}/settle", [
            'home_score' => 2,
            'away_score' => 2,
            'ht_home_score' => 1,
        ]);

        $result = Result::where('match_id', $match->id)->firstOrFail();

        $this->assertNull($result->ht_home_score);
        $this->assertArrayNotHasKey('ht_win', $result->actual_outcome);
    }
}
