<?php

namespace Tests\Feature;

use App\Contracts\FixtureProvider;
use App\Jobs\FixtureIngestionJob;
use App\Models\GameMatch;
use App\Models\Setting;
use App\Services\Fixtures\FootballDataProvider;
use App\Services\PreviewGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * When previews get written, how often, and what the home page shows.
 */
class PreviewPublishingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('prediction_provider', 'poisson_xg');
        Setting::set('football_data_token', 'test-token');
        Setting::set('gemini_api_key', 'test-gemini-key');
        $this->app->bind(FixtureProvider::class, fn () => new FootballDataProvider);
    }

    protected function fakeFeeds(int $daysAhead = 6, bool $geminiWorks = true): void
    {
        $gemini = $geminiWorks
            ? Http::response(['candidates' => [['content' => ['parts' => [['text' => 'A model-written preview.']]]]]])
            : Http::response('', 503);

        Http::fake([
            '*generativelanguage.googleapis.com*' => $gemini,
            '*/competitions/*/standings' => Http::response(['standings' => []]),
            '*/matches*' => Http::response(['matches' => [[
                'id' => 8001,
                'utcDate' => now()->addDays($daysAhead)->toIso8601String(),
                'status' => 'SCHEDULED',
                'competition' => ['name' => 'Premier League', 'code' => 'PL'],
                'homeTeam' => ['name' => 'Arsenal'],
                'awayTeam' => ['name' => 'Chelsea'],
            ]]]),
        ]);
    }

    protected function ingest(): void
    {
        app(FixtureIngestionJob::class)->handle(
            app(FixtureProvider::class),
            app(\App\Services\PredictionService::class),
            app(PreviewGenerationService::class),
        );
    }

    /**
     * The behaviour this replaced: every nightly run rewrote every preview, so
     * a fixture a week out was sent to the model seven times and its published
     * copy changed under readers seven times.
     */
    public function test_a_preview_is_written_once_not_on_every_run(): void
    {
        $this->fakeFeeds(daysAhead: 6);

        $this->ingest();
        $this->ingest();
        $this->ingest();

        $match = GameMatch::firstOrFail();

        $this->assertSame(GameMatch::PREVIEW_SOURCE_MODEL, $match->preview_source);
        $this->assertNotNull($match->preview_generated_at);
        $this->assertFalse($match->needsPreview(), 'a written preview outside the refresh window is done');

        // The point of the whole change: one model call across three runs, not
        // one per run. Counted against the model host specifically, since the
        // fixture feed is called every pass by design.
        $geminiCalls = 0;

        Http::recorded(function ($request) use (&$geminiCalls) {
            if (str_contains($request->url(), 'generativelanguage.googleapis.com')) {
                $geminiCalls++;
            }
        });

        $this->assertSame(1, $geminiCalls);
    }

    /**
     * Content still goes out across the whole lead window; only the rewriting
     * stopped. A fixture six days away has its preview immediately.
     */
    public function test_content_is_published_across_the_full_lead_window(): void
    {
        $this->fakeFeeds(daysAhead: 6);
        $this->ingest();

        $match = GameMatch::firstOrFail();

        $this->assertNotEmpty($match->preview_text);
        $this->assertTrue($match->previewIsPublishable());
    }

    public function test_the_preview_is_rewritten_once_close_to_kickoff(): void
    {
        $this->fakeFeeds(daysAhead: 6);
        $this->ingest();

        $match = GameMatch::firstOrFail();
        $this->assertNull($match->preview_refreshed_at);

        // Inside the refresh window it earns exactly one rewrite.
        $match->update(['kickoff_at' => now()->addDay()]);
        $this->assertTrue($match->fresh()->needsPreview());

        app(PreviewGenerationService::class)->generatePreview($match->fresh());

        $refreshed = $match->fresh();
        $this->assertNotNull($refreshed->preview_refreshed_at);
        $this->assertFalse($refreshed->needsPreview(), 'the refresh is a one-off, not a new daily cycle');
    }

    /**
     * The failure the write-once rule would otherwise bake in: an outage during
     * the nightly run leaves boilerplate on a page that is written to be
     * indexed, and nothing would ever come back to replace it.
     */
    public function test_a_fallback_preview_is_retried_on_the_next_run(): void
    {
        // One fake, two outcomes: the model is down for the first run and back
        // for the second. Re-faking mid-test would not work — Http::fake merges
        // stubs and the first match wins, so the outage would never clear.
        Http::fake([
            '*generativelanguage.googleapis.com*' => Http::sequence()
                ->push('', 503)
                ->push(['candidates' => [['content' => ['parts' => [['text' => 'A model-written preview.']]]]]]),
            '*/competitions/*/standings' => Http::response(['standings' => []]),
            '*/matches*' => Http::response(['matches' => [[
                'id' => 8001,
                'utcDate' => now()->addDays(6)->toIso8601String(),
                'status' => 'SCHEDULED',
                'competition' => ['name' => 'Premier League', 'code' => 'PL'],
                'homeTeam' => ['name' => 'Arsenal'],
                'awayTeam' => ['name' => 'Chelsea'],
            ]]]),
        ]);

        $this->ingest();

        $match = GameMatch::firstOrFail();

        $this->assertSame(GameMatch::PREVIEW_SOURCE_FALLBACK, $match->preview_source);
        $this->assertTrue($match->needsPreview(), 'boilerplate must not be treated as finished');

        $this->ingest();

        $recovered = GameMatch::firstOrFail();
        $this->assertSame(GameMatch::PREVIEW_SOURCE_MODEL, $recovered->preview_source);
        $this->assertSame('A model-written preview.', $recovered->preview_text);
        $this->assertFalse($recovered->needsPreview(), 'a real preview is not retried again');
    }

    public function test_a_started_fixture_is_never_rewritten(): void
    {
        $this->fakeFeeds(daysAhead: 6);
        $this->ingest();

        $match = GameMatch::firstOrFail();
        $match->update(['kickoff_at' => now()->subHour(), 'preview_source' => GameMatch::PREVIEW_SOURCE_FALLBACK]);

        $this->assertFalse($match->fresh()->needsPreview());
    }

    /**
     * The point of splitting the two settings: the front page can be tightened
     * without any fixture losing its page.
     */
    public function test_the_home_page_window_is_independent_of_the_content_window(): void
    {
        Setting::set('preview_lead_days', '7');
        Setting::set('home_fixture_days', '2');

        $soon = GameMatch::create([
            'home_team' => 'Near FC', 'away_team' => 'Soon United',
            'league' => 'Premier League', 'kickoff_at' => now()->addDay(),
            'preview_text' => 'ready',
        ]);

        $later = GameMatch::create([
            'home_team' => 'Later FC', 'away_team' => 'Distant Town',
            'league' => 'Premier League', 'kickoff_at' => now()->addDays(5),
            'preview_text' => 'ready',
        ]);

        $featured = GameMatch::forHomeListing()->pluck('id');

        $this->assertContains($soon->id, $featured->all());
        $this->assertNotContains($later->id, $featured->all(), 'beyond the home window it is not featured');

        // But it keeps its page and its published preview.
        $this->assertContains($later->id, GameMatch::withinPreviewWindow()->pluck('id')->all());
        $this->assertTrue($later->previewIsPublishable());
        $this->get(route('matches.show', $later))->assertOk()->assertSee('Later FC');
    }

    public function test_the_home_window_cannot_exceed_the_content_window(): void
    {
        Setting::set('preview_lead_days', '3');
        Setting::set('home_fixture_days', '10');

        // Listing fixtures that were never ingested would show empty pages.
        $this->assertSame(3, GameMatch::homeFixtureDays());
    }

    public function test_the_home_window_defaults_to_the_content_window(): void
    {
        Setting::set('preview_lead_days', '7');

        $this->assertSame(7, GameMatch::homeFixtureDays());
    }

    public function test_an_admin_can_set_both_windows(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/settings', [
            'fixture_provider' => 'football_data',
            'paypal_mode' => 'sandbox',
            'prediction_provider' => 'poisson_xg',
            'min_confidence_threshold' => '0.55',
            'home_advantage' => '1.15',
            'default_league_average' => '1.35',
            'preview_lead_days' => '7',
            'home_fixture_days' => '2',
            'preview_refresh_days' => '3',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame(7, GameMatch::previewLeadDays());
        $this->assertSame(2, GameMatch::homeFixtureDays());
        $this->assertSame(3, GameMatch::previewRefreshDays());
    }
}
