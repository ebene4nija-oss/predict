<?php

namespace Tests\Feature;

use App\Models\GameMatch;
use App\Models\Prediction;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchPreviewsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function createSampleMatches(): void
    {
        $match1 = GameMatch::create([
            'home_team' => 'Arsenal',
            'away_team' => 'Chelsea',
            'league' => 'Premier League',
            'kickoff_at' => now()->addHours(6),
            'status' => 'TIMED',
            'preview_headline' => 'North London tactical battle',
            'preview_text' => 'Arsenal enter this fixture aiming to secure top spot in the league.',
        ]);

        Prediction::create([
            'match_id' => $match1->id,
            'market' => 'win',
            'pick' => 'Arsenal Win',
            'probability' => 0.72,
            'odds' => 1.65,
            'is_ai5' => true,
            'is_top10' => true,
            'published_at' => now(),
        ]);

        $match2 = GameMatch::create([
            'home_team' => 'Real Madrid',
            'away_team' => 'Barcelona',
            'league' => 'La Liga',
            'kickoff_at' => now()->addHours(12),
            'status' => 'TIMED',
            'preview_headline' => 'El Clasico clash in Madrid',
            'preview_text' => 'Real Madrid host Barcelona in a crucial title showdown at the Bernabeu.',
        ]);

        Prediction::create([
            'match_id' => $match2->id,
            'market' => 'over_2_5',
            'pick' => 'Over 2.5 Goals',
            'probability' => 0.81,
            'odds' => 1.55,
            'is_ai5' => true,
            'is_top10' => true,
            'published_at' => now(),
        ]);
    }

    public function test_matches_index_page_is_publicly_accessible(): void
    {
        $this->createSampleMatches();

        $response = $this->get(route('matches.index'));

        $response->assertOk();
        $response->assertSee('Football Match Previews & AI Predictions', false);
        $response->assertSee('Arsenal vs Chelsea', false);
        $response->assertSee('Real Madrid vs Barcelona', false);
        $response->assertSee('Premier League');
        $response->assertSee('La Liga');
        $response->assertSee('Read Preview & Stats');
        $response->assertSee('<link rel="canonical" href="' . route('matches.index') . '"', false);
        $response->assertSee('CollectionPage');
    }

    public function test_match_previews_alias_route_works(): void
    {
        $this->createSampleMatches();

        $response = $this->get(route('matches.previews'));

        $response->assertOk();
        $response->assertSee('Football Match Previews');
    }

    public function test_matches_index_filters_by_league(): void
    {
        $this->createSampleMatches();

        $response = $this->get(route('matches.index', ['league' => 'Premier League']));

        $response->assertOk();
        $response->assertSee('Arsenal enter this fixture');
        $response->assertDontSee('Real Madrid host Barcelona in a crucial title showdown');
    }

    public function test_matches_index_filters_by_search_keyword(): void
    {
        $this->createSampleMatches();

        $response = $this->get(route('matches.index', ['search' => 'Barcelona']));

        $response->assertOk();
        $response->assertSee('Real Madrid host Barcelona in a crucial title showdown');
        $response->assertDontSee('Arsenal enter this fixture');
    }

    public function test_matches_index_renders_sportybet_booking_code(): void
    {
        $this->createSampleMatches();
        Setting::set('sportybet_top5_booking_code', 'SPB-ALL-PREVIEWS');

        $response = $this->get(route('matches.index'));

        $response->assertOk();
        $response->assertSee('SPB-ALL-PREVIEWS');
        $response->assertSee('Copy SportyBet Code');
    }

    public function test_sitemap_includes_matches_index(): void
    {
        $response = $this->get(route('sitemap.xml'));

        $response->assertOk();
        $response->assertSee('<loc>' . route('matches.index') . '</loc>', false);
    }
}
