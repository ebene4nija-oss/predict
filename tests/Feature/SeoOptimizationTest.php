<?php

namespace Tests\Feature;

use App\Models\GameMatch;
use App\Models\Post;
use App\Models\Prediction;
use App\Support\MarketRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoOptimizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_match_page_resolves_with_and_without_slug_and_has_canonical(): void
    {
        $match = GameMatch::create([
            'home_team' => 'Arsenal',
            'away_team' => 'Chelsea',
            'league' => 'Premier League',
            'kickoff_at' => now()->addDays(2),
            'preview_text' => 'High stakes London derby tactical analysis.',
            'seo_title' => 'Arsenal vs Chelsea AI Prediction & Tactical Preview',
            'seo_description' => 'Detailed statistical preview and betting prediction for Arsenal vs Chelsea.',
        ]);

        // Access via canonical slugged URL
        $slugUrl = route('matches.show', ['match' => $match->id, 'slug' => $match->slug()]);
        $response = $this->get($slugUrl);
        $response->assertOk();
        $response->assertSee('Arsenal vs Chelsea');
        $response->assertSee('<link rel="canonical" href="' . $match->canonicalUrl() . '"', false);
        $response->assertSee('BreadcrumbList');
        $response->assertSee('SportsEvent');

        // Access via numeric ID without slug (backward compatibility)
        $idUrl = route('matches.show', ['match' => $match->id]);
        $response2 = $this->get($idUrl);
        $response2->assertOk();
        $response2->assertSee('Arsenal vs Chelsea');
        $response2->assertSee('<link rel="canonical" href="' . $match->canonicalUrl() . '"', false);
    }

    public function test_top_picks_clean_market_routes_work_for_all_listed_markets(): void
    {
        foreach (MarketRegistry::listed() as $marketKey => $marketDef) {
            $url = route('top.picks', ['market' => $marketKey]);
            $response = $this->get($url);

            $response->assertOk();
            $response->assertSee($marketDef->listLabel);
            $response->assertSee('<link rel="canonical" href="' . $url . '"', false);
            $response->assertSee('CollectionPage');
            $response->assertSee('ItemList');
        }
    }

    public function test_public_landing_pages_have_canonical_and_meta_tags(): void
    {
        $pages = [
            route('home') => ['AI Football Predictions', 'Organization'],
            route('track-record') => ['Public Track Record', 'track-record'],
            route('how-ai-works') => ['How', 'Poisson'],
            route('expert.picks') => ['Human Expert Picks', 'expert-picks'],
            route('expert.leaderboard') => ['Expert Analysts', 'expert-leaderboard'],
            route('subscription.pricing') => ['PRO', 'subscribe'],
            route('blog.index') => ['News &amp; Announcements', 'news'],
            route('legal.terms') => ['Terms of Service', 'terms'],
            route('legal.privacy') => ['Privacy Policy', 'privacy'],
            route('legal.refunds') => ['Refund', 'refunds'],
        ];

        foreach ($pages as $url => [$expectedText, $canonicalSnippet]) {
            $response = $this->get($url);
            $response->assertOk();
            $response->assertSee($expectedText, false);
            $response->assertSee('<link rel="canonical" href="' . $url . '"', false);
        }
    }

    public function test_sitemap_xml_indexes_all_market_routes_slugged_matches_and_posts(): void
    {
        $match = GameMatch::create([
            'home_team' => 'Liverpool',
            'away_team' => 'Real Madrid',
            'league' => 'Champions League',
            'kickoff_at' => now()->addDays(3),
        ]);

        $post = Post::create([
            'title' => 'Championship Tactical Insights',
            'slug' => 'championship-tactical-insights',
            'body' => 'Detailed analysis of recent football trends.',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get(route('sitemap.xml'));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/xml; charset=UTF-8');

        // All 7 listed markets
        foreach (MarketRegistry::listed() as $marketKey => $marketDef) {
            $response->assertSee('<loc>' . route('top.picks', ['market' => $marketKey]) . '</loc>', false);
        }

        // Slugged match
        $response->assertSee('<loc>' . $match->canonicalUrl() . '</loc>', false);

        // Published post
        $response->assertSee('<loc>' . route('blog.show', $post) . '</loc>', false);
    }

    public function test_robots_txt_points_to_sitemap(): void
    {
        $response = $this->get(route('robots.txt'));
        $response->assertOk();
        $response->assertSee('User-agent: *');
        $response->assertSee('Sitemap: ' . route('sitemap.xml'));
    }
}
