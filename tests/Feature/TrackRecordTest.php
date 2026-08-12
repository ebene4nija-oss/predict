<?php

namespace Tests\Feature;

use App\Models\GameMatch;
use App\Models\Prediction;
use App\Models\Result;
use App\Services\TrackRecordService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_track_record_service_calculates_accuracy_automatically(): void
    {
        $match = GameMatch::create([
            'home_team' => 'Real Madrid',
            'away_team' => 'Barcelona',
            'league' => 'La Liga',
            'kickoff_at' => now()->subDay(),
        ]);

        Prediction::create([
            'match_id' => $match->id,
            'market' => 'win_draw_loss',
            'pick' => 'Home Win',
            'probability' => 0.85,
        ]);

        Result::create([
            'match_id' => $match->id,
            'home_score' => 3,
            'away_score' => 1,
            'settled_at' => now(),
        ]);

        $service = new TrackRecordService();
        $stats = $service->getAccuracyStats();

        $this->assertEquals(1, $stats['ai']['win_draw_loss']['total']);
        $this->assertEquals(1, $stats['ai']['win_draw_loss']['won']);
        $this->assertEquals(100.0, $stats['ai']['win_draw_loss']['rate']);
    }
}
