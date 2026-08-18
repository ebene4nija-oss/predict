<?php

namespace Tests\Feature;

use App\Models\GameMatch;
use App\Models\Setting;
use App\Models\User;
use App\Services\PreviewGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminPreviewManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->user = User::factory()->create(['role' => 'free']);
    }

    public function test_guests_and_regular_users_cannot_access_admin_previews(): void
    {
        $match = GameMatch::create([
            'home_team' => 'Arsenal',
            'away_team' => 'Chelsea',
            'league' => 'Premier League',
            'kickoff_at' => now()->addDays(2),
        ]);

        $this->get(route('admin.previews.index'))->assertRedirect(route('login'));
        $this->get(route('admin.previews.edit', $match))->assertRedirect(route('login'));

        $this->actingAs($this->user)->get(route('admin.previews.index'))->assertRedirect(route('home'));
        $this->actingAs($this->user)->get(route('admin.previews.edit', $match))->assertRedirect(route('home'));
    }

    public function test_admin_can_view_previews_index_with_kpi_counts_and_filters(): void
    {
        GameMatch::create([
            'home_team' => 'Arsenal',
            'away_team' => 'Chelsea',
            'league' => 'Premier League',
            'kickoff_at' => now()->addDays(2),
            'preview_text' => 'Arsenal preview text',
            'preview_source' => GameMatch::PREVIEW_SOURCE_MODEL,
            'preview_status' => GameMatch::PREVIEW_STATUS_PUBLISHED,
        ]);

        GameMatch::create([
            'home_team' => 'Real Madrid',
            'away_team' => 'Barcelona',
            'league' => 'La Liga',
            'kickoff_at' => now()->addDays(3),
            'preview_text' => null,
            'preview_status' => GameMatch::PREVIEW_STATUS_DRAFT,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.previews.index'));

        $response->assertOk();
        $response->assertSee('Match Previews &amp; SEO Manager', false);
        $response->assertSee('Arsenal');
        $response->assertSee('Real Madrid');

        // Test filter
        $missingResponse = $this->actingAs($this->admin)->get(route('admin.previews.index', ['filter' => 'missing']));
        $missingResponse->assertOk();
        $missingResponse->assertSee('Real Madrid');
        $missingResponse->assertDontSee('Arsenal');

        // Test search
        $searchResponse = $this->actingAs($this->admin)->get(route('admin.previews.index', ['search' => 'Barcelona']));
        $searchResponse->assertOk();
        $searchResponse->assertSee('Real Madrid');
        $searchResponse->assertDontSee('Arsenal');
    }

    public function test_admin_can_open_preview_edit_page(): void
    {
        $match = GameMatch::create([
            'home_team' => 'Liverpool',
            'away_team' => 'Man City',
            'league' => 'Premier League',
            'kickoff_at' => now()->addDays(2),
            'preview_text' => '## Tactical Context',
            'seo_title' => 'Liverpool vs Man City AI Preview',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.previews.edit', $match));

        $response->assertOk();
        $response->assertSee('Liverpool');
        $response->assertSee('Man City');
        $response->assertSee('Google Search (SERP) Live Simulator');
        $response->assertSee('Liverpool vs Man City AI Preview');
    }

    public function test_admin_can_update_preview_content_and_seo_fields(): void
    {
        $match = GameMatch::create([
            'home_team' => 'Bayern Munich',
            'away_team' => 'Dortmund',
            'league' => 'Bundesliga',
            'kickoff_at' => now()->addDays(2),
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.previews.update', $match), [
            'preview_headline' => 'Bayern vs Dortmund Der Klassiker Tactical Preview & Score Prediction',
            'preview_text' => "## Match Overview & Tactical Context\nBayern host Dortmund in a thrilling Der Klassiker.\n\n## Score Prediction\nBayern to edge it.",
            'seo_title' => 'Bayern Munich vs Dortmund Prediction & AI Preview | Bundesliga',
            'seo_description' => 'Comprehensive Bayern Munich vs Borussia Dortmund prediction, expected goals xG breakdown, and tactical preview.',
            'seo_keywords' => 'bayern vs dortmund prediction, bundesliga xg betting analysis, der klassiker preview',
            'preview_status' => 'published',
            'is_preview_custom' => '1',
        ]);

        $response->assertRedirect(route('admin.previews.edit', $match));
        $response->assertSessionHas('success');

        $match->refresh();

        $this->assertSame('Bayern vs Dortmund Der Klassiker Tactical Preview & Score Prediction', $match->preview_headline);
        $this->assertStringContainsString('Der Klassiker', $match->preview_text);
        $this->assertSame('Bayern Munich vs Dortmund Prediction & AI Preview | Bundesliga', $match->seo_title);
        $this->assertSame('published', $match->preview_status);
        $this->assertTrue($match->is_preview_custom);
        $this->assertFalse($match->needsPreview(), 'custom preview is locked from background auto-overwrite');
    }

    public function test_admin_can_generate_ai_preview_on_demand(): void
    {
        Setting::set('gemini_api_key', 'test-gemini-key');

        Http::fake([
            '*generativelanguage.googleapis.com*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => "## Match Overview\nGenerated tactical narrative."]]]]
                ]
            ]),
        ]);

        $match = GameMatch::create([
            'home_team' => 'Inter',
            'away_team' => 'Juventus',
            'league' => 'Serie A',
            'kickoff_at' => now()->addDays(2),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.previews.generate', $match), [
            'instruction' => 'Focus on defensive records and midfield duel.',
        ]);

        $response->assertRedirect(route('admin.previews.edit', $match));
        $response->assertSessionHas('success');

        $match->refresh();

        $this->assertStringContainsString('Generated tactical narrative', $match->preview_text);
        $this->assertSame(GameMatch::PREVIEW_SOURCE_MODEL, $match->preview_source);
        $this->assertTrue($match->is_preview_custom, 'manual generation stamps as custom curation');
    }

    public function test_admin_can_bulk_generate_previews_for_upcoming_matches(): void
    {
        Setting::set('gemini_api_key', 'test-gemini-key');

        Http::fake([
            '*generativelanguage.googleapis.com*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => "## Tactical Analysis\nBulk generated."]]]]
                ]
            ]),
        ]);

        $match1 = GameMatch::create([
            'home_team' => 'PSG',
            'away_team' => 'Marseille',
            'league' => 'Ligue 1',
            'kickoff_at' => now()->addDays(2),
            'preview_text' => null,
        ]);

        $match2 = GameMatch::create([
            'home_team' => 'Lyon',
            'away_team' => 'Monaco',
            'league' => 'Ligue 1',
            'kickoff_at' => now()->addDays(3),
            'preview_text' => null,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.previews.bulk-generate'));

        $response->assertSessionHas('success');

        $this->assertNotEmpty($match1->fresh()->preview_text);
        $this->assertNotEmpty($match2->fresh()->preview_text);
    }

    public function test_admin_can_toggle_preview_publication_status(): void
    {
        $match = GameMatch::create([
            'home_team' => 'Milan',
            'away_team' => 'Napoli',
            'league' => 'Serie A',
            'kickoff_at' => now()->addDays(2),
            'preview_text' => 'Sample preview',
            'preview_status' => GameMatch::PREVIEW_STATUS_PUBLISHED,
        ]);

        $this->actingAs($this->admin)->post(route('admin.previews.toggle', $match));
        $this->assertSame(GameMatch::PREVIEW_STATUS_DRAFT, $match->fresh()->preview_status);
        $this->assertFalse($match->fresh()->previewIsPublishable());

        $this->actingAs($this->admin)->post(route('admin.previews.toggle', $match));
        $this->assertSame(GameMatch::PREVIEW_STATUS_PUBLISHED, $match->fresh()->preview_status);
        $this->assertTrue($match->fresh()->previewIsPublishable());
    }

    public function test_public_match_page_renders_seo_tags_and_markdown_preview(): void
    {
        $match = GameMatch::create([
            'home_team' => 'Tottenham',
            'away_team' => 'West Ham',
            'league' => 'Premier League',
            'kickoff_at' => now()->addDays(1),
            'preview_headline' => 'Spurs vs West Ham London Derby Tactical Breakdown',
            'preview_text' => "## Match Overview & Tactical Context\nSpurs host the Hammers in a London derby.\n\n## Score Prediction & Key Takeaways\n- **Home Form**: Spurs look strong at home.",
            'seo_title' => 'Tottenham vs West Ham Prediction & AI Preview | London Derby',
            'seo_description' => 'In-depth Tottenham vs West Ham betting analysis and Poisson expected goals preview.',
            'seo_keywords' => 'tottenham vs west ham prediction, spurs london derby xg',
            'preview_status' => GameMatch::PREVIEW_STATUS_PUBLISHED,
        ]);

        $response = $this->get(route('matches.show', $match));

        $response->assertOk();
        $response->assertSee('Tottenham vs West Ham Prediction &amp; AI Preview | London Derby', false);
        $response->assertSee('In-depth Tottenham vs West Ham betting analysis', false);
        $response->assertSee('Spurs vs West Ham London Derby Tactical Breakdown');
        $response->assertSee('<h2>Match Overview &amp; Tactical Context</h2>', false);
        $response->assertSee('FAQPage');
        $response->assertSee('SportsEvent');
        $response->assertSee('NewsArticle');
    }
}
