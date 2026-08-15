<?php

namespace Tests\Feature;

use App\Models\GameMatch;
use App\Models\Prediction;
use App\Services\PredictionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PredictionEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_poisson_prediction_service_computes_valid_probabilities(): void
    {
        $match = GameMatch::create([
            'home_team' => 'Arsenal',
            'away_team' => 'Chelsea',
            'league' => 'Premier League',
            'kickoff_at' => now()->addHours(6),
            'home_form' => ['gf' => 2.4, 'ga' => 0.8],
            'away_form' => ['gf' => 1.2, 'ga' => 1.6],
        ]);

        $predictions = app(PredictionService::class)->calculateAndStore($match);

        $this->assertArrayHasKey('win_draw_loss', $predictions);
        $this->assertArrayHasKey('gg', $predictions);
        $this->assertArrayHasKey('over_2_5', $predictions);

        $wdl = $predictions['win_draw_loss'];
        $this->assertGreaterThanOrEqual(0.0, $wdl->probability);
        $this->assertLessThanOrEqual(1.0, $wdl->probability);
        $this->assertEquals('Home Win', $wdl->pick); // Arsenal strong gf + Chelsea weak ga

        $this->assertDatabaseHas('predictions', [
            'match_id' => $match->id,
            'market' => 'win_draw_loss',
        ]);
    }
}
