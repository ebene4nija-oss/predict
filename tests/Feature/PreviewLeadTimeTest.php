<?php

namespace Tests\Feature;

use App\Contracts\FixtureProvider;
use App\Jobs\FixtureIngestionJob;
use App\Models\GameMatch;
use App\Models\Setting;
use App\Models\User;
use App\Services\Fixtures\FootballDataProvider;
use App\Services\PredictionService;
use App\Services\PreviewGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PreviewLeadTimeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('prediction_provider', 'poisson_xg');
        Setting::set('football_data_token', 'test-token');
        $this->app->bind(FixtureProvider::class, fn () => new FootballDataProvider);
    }

    public function test_lead_time_defaults_to_seven_days(): void
    {
        $this->assertSame(7, GameMatch::previewLeadDays());
    }

    public function test_lead_time_is_clamped_to_the_supported_range(): void
    {
        // The settings table is writable outside the validated admin form, so
        // a nonsense value must not translate into a nonsense ingestion window.
        Setting::set('preview_lead_days', '999');
        $this->assertSame(GameMatch::MAX_PREVIEW_LEAD_DAYS, GameMatch::previewLeadDays());

        Setting::set('preview_lead_days', '0');
        $this->assertSame(GameMatch::DEFAULT_PREVIEW_LEAD_DAYS, GameMatch::previewLeadDays());

        Setting::set('preview_lead_days', '-4');
        $this->assertSame(1, GameMatch::previewLeadDays());
    }

    public function test_the_configured_lead_time_drives_the_fixture_request_window(): void
    {
        Setting::set('preview_lead_days', '3');

        Http::fake([
            '*/competitions/*/standings' => Http::response(['standings' => []]),
            '*/matches*' => Http::response(['matches' => []]),
        ]);

        app(FixtureIngestionJob::class)->handle(
            app(FixtureProvider::class),
            app(PredictionService::class),
            app(PreviewGenerationService::class),
        );

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'dateTo='.now()->addDays(3)->toDateString());
        });
    }

    public function test_an_explicit_day_count_overrides_the_setting_for_backfills(): void
    {
        Setting::set('preview_lead_days', '3');

        Http::fake([
            '*/competitions/*/standings' => Http::response(['standings' => []]),
            '*/matches*' => Http::response(['matches' => []]),
        ]);

        (new FixtureIngestionJob(days: 10))->handle(
            app(FixtureProvider::class),
            app(PredictionService::class),
            app(PreviewGenerationService::class),
        );

        Http::assertSent(fn ($request) => str_contains($request->url(), 'dateTo='.now()->addDays(10)->toDateString()));
    }

    public function test_fixtures_beyond_the_window_are_excluded_from_public_listings(): void
    {
        Setting::set('preview_lead_days', '2');

        $inside = GameMatch::create([
            'home_team' => 'Arsenal', 'away_team' => 'Chelsea', 'league' => 'PL',
            'kickoff_at' => now()->addDay(),
        ]);

        $outside = GameMatch::create([
            'home_team' => 'Leeds', 'away_team' => 'Everton', 'league' => 'PL',
            'kickoff_at' => now()->addDays(9),
        ]);

        $visible = GameMatch::withinPreviewWindow()->pluck('id');

        $this->assertTrue($visible->contains($inside->id));
        $this->assertFalse($visible->contains($outside->id));
    }

    public function test_a_preview_outside_the_window_is_withheld_from_the_match_page(): void
    {
        Setting::set('preview_lead_days', '5');

        $match = GameMatch::create([
            'home_team' => 'Arsenal', 'away_team' => 'Chelsea', 'league' => 'Premier League',
            'kickoff_at' => now()->addDays(30),
            'preview_text' => 'A tactical breakdown written far too early.',
        ]);

        $this->assertFalse($match->previewIsPublishable());

        $this->get(route('matches.show', $match))
            ->assertOk()
            ->assertDontSee('A tactical breakdown written far too early.')
            ->assertSee('published 5 days before kickoff', false);
    }

    public function test_a_preview_inside_the_window_is_shown(): void
    {
        Setting::set('preview_lead_days', '7');

        $match = GameMatch::create([
            'home_team' => 'Arsenal', 'away_team' => 'Chelsea', 'league' => 'Premier League',
            'kickoff_at' => now()->addDays(2),
            'preview_text' => 'Arsenal host Chelsea in a tight London derby.',
        ]);

        $this->assertTrue($match->previewIsPublishable());

        $this->get(route('matches.show', $match))
            ->assertOk()
            ->assertSee('Arsenal host Chelsea in a tight London derby.');
    }

    public function test_an_admin_can_change_the_lead_time_from_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);

        $this->actingAs($admin)
            ->post(route('admin.settings.update'), [
                'fixture_provider' => 'football_data',
                'paypal_mode' => 'sandbox',
                'prediction_provider' => 'poisson_xg',
                'min_confidence_threshold' => '0.55',
                'home_advantage' => '1.3',
                'default_league_average' => '1.35',
                'preview_lead_days' => '4',
            ])
            ->assertRedirect(route('admin.settings'))
            ->assertSessionHasNoErrors();

        $this->assertSame(4, GameMatch::previewLeadDays());
    }

    public function test_a_lead_time_over_the_ceiling_is_rejected_by_the_form(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);

        $this->actingAs($admin)
            ->post(route('admin.settings.update'), [
                'fixture_provider' => 'football_data',
                'paypal_mode' => 'sandbox',
                'prediction_provider' => 'poisson_xg',
                'min_confidence_threshold' => '0.55',
                'home_advantage' => '1.3',
                'default_league_average' => '1.35',
                'preview_lead_days' => '60',
            ])
            ->assertSessionHasErrors('preview_lead_days');
    }
}
