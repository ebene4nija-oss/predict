<?php

namespace Tests\Feature;

use App\Contracts\FixtureProvider;
use App\Jobs\FixtureIngestionJob;
use App\Models\GameMatch;
use App\Models\Setting;
use App\Models\Team;
use App\Models\User;
use App\Services\Fixtures\FootballDataProvider;
use App\Services\PredictionService;
use App\Services\PreviewGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TeamCrestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('prediction_provider', 'poisson_xg');
        Setting::set('football_data_token', 'test-token');
        $this->app->bind(FixtureProvider::class, fn () => new FootballDataProvider);
    }

    protected function ingest(array $matches): void
    {
        Http::fake([
            '*/competitions/*/standings' => Http::response(['standings' => []]),
            '*/matches*' => Http::response(['matches' => $matches]),
        ]);

        app(FixtureIngestionJob::class)->handle(
            app(FixtureProvider::class),
            app(PredictionService::class),
            app(PreviewGenerationService::class),
        );
    }

    protected function fixturePayload(): array
    {
        return [
            [
                'id' => 2001,
                'utcDate' => now()->addDays(2)->toIso8601String(),
                'status' => 'SCHEDULED',
                'competition' => ['name' => 'Premier League', 'code' => 'PL'],
                'homeTeam' => ['id' => 57, 'name' => 'Arsenal FC', 'shortName' => 'Arsenal', 'tla' => 'ARS', 'crest' => 'https://crests.example/57.png'],
                'awayTeam' => ['id' => 61, 'name' => 'Chelsea FC', 'shortName' => 'Chelsea', 'tla' => 'CHE', 'crest' => 'https://crests.example/61.png'],
            ],
        ];
    }

    public function test_ingestion_captures_crests_the_provider_supplies(): void
    {
        $this->ingest($this->fixturePayload());

        $arsenal = Team::firstWhere('external_id', '57');

        $this->assertNotNull($arsenal);
        $this->assertSame('Arsenal FC', $arsenal->name);
        $this->assertSame('ARS', $arsenal->tla);
        $this->assertSame('https://crests.example/57.png', $arsenal->crest());
    }

    public function test_fixtures_are_linked_to_both_clubs(): void
    {
        $this->ingest($this->fixturePayload());

        $match = GameMatch::firstWhere('external_id', '2001');

        $this->assertNotNull($match->home_team_id);
        $this->assertNotNull($match->away_team_id);
        $this->assertSame('https://crests.example/57.png', $match->crestFor('home'));
        $this->assertSame('https://crests.example/61.png', $match->crestFor('away'));
    }

    public function test_a_club_is_reused_across_fixtures_rather_than_duplicated(): void
    {
        $payload = $this->fixturePayload();
        $payload[] = [
            'id' => 2002,
            'utcDate' => now()->addDays(4)->toIso8601String(),
            'status' => 'SCHEDULED',
            'competition' => ['name' => 'Premier League', 'code' => 'PL'],
            'homeTeam' => ['id' => 61, 'name' => 'Chelsea FC', 'shortName' => 'Chelsea', 'tla' => 'CHE', 'crest' => 'https://crests.example/61.png'],
            'awayTeam' => ['id' => 57, 'name' => 'Arsenal FC', 'shortName' => 'Arsenal', 'tla' => 'ARS', 'crest' => 'https://crests.example/57.png'],
        ];

        $this->ingest($payload);

        $this->assertSame(2, Team::count());
        $this->assertSame(2, GameMatch::count());
    }

    public function test_an_admin_crest_override_survives_re_ingestion(): void
    {
        $this->ingest($this->fixturePayload());

        $arsenal = Team::firstWhere('external_id', '57');
        $arsenal->update(['custom_crest_url' => 'https://cdn.example/our-arsenal.png']);

        $this->ingest($this->fixturePayload());

        $this->assertSame('https://cdn.example/our-arsenal.png', $arsenal->fresh()->crest());
    }

    public function test_backfilled_clubs_are_adopted_rather_than_duplicated_on_ingestion(): void
    {
        // A fixture that predates the crest library: strings only, no club ids.
        GameMatch::create([
            'external_id' => '2001',
            'home_team' => 'Arsenal FC',
            'away_team' => 'Chelsea FC',
            'league' => 'Premier League',
            'kickoff_at' => now()->addDays(2),
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]))
            ->post(route('admin.teams.backfill'))
            ->assertRedirect();

        $this->assertSame(2, Team::count());
        $this->assertNotNull(GameMatch::firstWhere('external_id', '2001')->home_team_id);

        // The next ingestion must claim those rows, not create a second
        // Arsenal keyed on the provider id.
        $this->ingest($this->fixturePayload());

        $this->assertSame(2, Team::count());
        $this->assertSame('https://crests.example/57.png', Team::firstWhere('name', 'Arsenal FC')->crest());
    }

    public function test_a_club_without_a_crest_falls_back_to_initials(): void
    {
        $team = Team::create(['name' => 'Real Sociedad']);

        $this->assertNull($team->crest());
        $this->assertSame('RS', $team->initials());

        $team->update(['tla' => 'RSO']);
        $this->assertSame('RSO', $team->fresh()->initials());
    }

    public function test_the_crest_library_is_reachable_by_an_admin(): void
    {
        $this->ingest($this->fixturePayload());

        $this->actingAs(User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]))
            ->get(route('admin.teams.index'))
            ->assertOk()
            ->assertSee('Arsenal FC')
            ->assertSee('https://crests.example/57.png');
    }
}
