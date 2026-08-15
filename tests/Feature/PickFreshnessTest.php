<?php

namespace Tests\Feature;

use App\Jobs\Ai5SelectionJob;
use App\Jobs\RankingJob;
use App\Models\GameMatch;
use App\Models\Prediction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PickFreshnessTest extends TestCase
{
    use RefreshDatabase;

    protected function makePrediction(GameMatch $match, float $probability): Prediction
    {
        return Prediction::create([
            'match_id' => $match->id,
            'market' => 'win_draw_loss',
            'pick' => 'Home Win',
            'probability' => $probability,
            'is_top10' => false,
            'is_ai5' => false,
        ]);
    }

    protected function upcomingMatch(): GameMatch
    {
        return GameMatch::create([
            'home_team' => 'Future FC',
            'away_team' => 'Tomorrow United',
            'league' => 'Premier League',
            'kickoff_at' => now()->addDay(),
        ]);
    }

    protected function finishedMatch(): GameMatch
    {
        return GameMatch::create([
            'home_team' => 'Past FC',
            'away_team' => 'Yesterday Town',
            'league' => 'Premier League',
            'kickoff_at' => now()->subWeek(),
        ]);
    }

    public function test_ranking_skips_matches_that_have_already_been_played(): void
    {
        // The stale pick has the higher probability, so it would win on
        // probability alone.
        $stale = $this->makePrediction($this->finishedMatch(), 0.95);
        $fresh = $this->makePrediction($this->upcomingMatch(), 0.60);

        (new RankingJob())->handle();

        $this->assertFalse($stale->fresh()->is_top10, 'a played fixture must not be ranked');
        $this->assertTrue($fresh->fresh()->is_top10);
    }

    public function test_ai_top_5_selection_skips_matches_that_have_already_been_played(): void
    {
        $stale = $this->makePrediction($this->finishedMatch(), 0.95);
        $fresh = $this->makePrediction($this->upcomingMatch(), 0.60);

        (new Ai5SelectionJob())->handle();

        $this->assertFalse($stale->fresh()->is_ai5, 'a played fixture must not be an AI top 5 pick');
        $this->assertTrue($fresh->fresh()->is_ai5);
    }

    public function test_top_picks_page_does_not_list_played_fixtures(): void
    {
        $this->makePrediction($this->finishedMatch(), 0.95)->update(['is_top10' => true, 'is_ai5' => true]);
        $this->makePrediction($this->upcomingMatch(), 0.60)->update(['is_top10' => true]);

        $response = $this->get('/top-picks');

        $response->assertOk();
        $response->assertSee('Future FC');
        $response->assertDontSee('Past FC');
    }

    public function test_home_page_does_not_list_played_fixtures_in_the_ai_top_5(): void
    {
        $this->makePrediction($this->finishedMatch(), 0.95)->update(['is_ai5' => true]);
        $this->makePrediction($this->upcomingMatch(), 0.60)->update(['is_ai5' => true]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('Yesterday Town');
    }
}
