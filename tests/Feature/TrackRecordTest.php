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
            // Locked before kickoff — only such picks count toward the record.
            'published_at' => $match->kickoff_at->copy()->subHours(2),
        ]);

        Result::create([
            'match_id' => $match->id,
            'home_score' => 3,
            'away_score' => 1,
            'settled_at' => now(),
        ]);

        $stats = app(TrackRecordService::class)->getAccuracyStats();

        $this->assertEquals(1, $stats['ai']['win_draw_loss']['total']);
        $this->assertEquals(1, $stats['ai']['win_draw_loss']['won']);
        $this->assertEquals(100.0, $stats['ai']['win_draw_loss']['rate']);
    }

    /**
     * The point of the audit trail: a pick written after the whistle proves
     * nothing and must not inflate the published record.
     */
    public function test_predictions_locked_after_kickoff_are_excluded(): void
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
            'probability' => 0.99,
            'published_at' => $match->kickoff_at->copy()->addHours(3),
        ]);

        Result::create([
            'match_id' => $match->id,
            'home_score' => 3,
            'away_score' => 1,
            'settled_at' => now(),
        ]);

        $stats = app(TrackRecordService::class)->getAccuracyStats();

        $this->assertSame(0, $stats['ai']['win_draw_loss']['total'], 'a post-kickoff pick must not be graded');
        $this->assertSame(0, $stats['ai']['overall']['total']);
    }

    public function test_roi_is_reported_from_odds_rather_than_hit_rate_alone(): void
    {
        // Two picks at short odds: one wins, one loses. Hit rate 50%, but at
        // 1.40 the book, the punter is down — which hit rate alone would hide.
        foreach ([[3, 1, 'won'], [0, 2, 'lost']] as $index => [$home, $away, $_label]) {
            $match = GameMatch::create([
                'home_team' => "Home {$index}",
                'away_team' => "Away {$index}",
                'league' => 'La Liga',
                'kickoff_at' => now()->subDays(2),
            ]);

            Prediction::create([
                'match_id' => $match->id,
                'market' => 'win_draw_loss',
                'pick' => 'Home Win',
                'probability' => 0.7,
                'odds' => 1.40,
                'published_at' => $match->kickoff_at->copy()->subHour(),
            ]);

            Result::create([
                'match_id' => $match->id,
                'home_score' => $home,
                'away_score' => $away,
                'settled_at' => now(),
            ]);
        }

        $stats = app(TrackRecordService::class)->getAccuracyStats();

        $this->assertSame(50.0, $stats['ai']['win_draw_loss']['rate']);
        // (+0.40 - 1.00) / 2 staked = -30%
        $this->assertSame(-30.0, $stats['ai']['win_draw_loss']['roi']);
    }
}
