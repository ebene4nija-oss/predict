<?php

namespace App\Services;

use App\Models\Prediction;
use App\Models\ExpertPick;
use App\Models\Result;
use App\Models\Expert;
use Illuminate\Support\Facades\DB;

class TrackRecordService
{
    /**
     * Get overall accuracy statistics for AI and Experts by market.
     */
    public function getAccuracyStats(): array
    {
        $settledResults = Result::with(['match.predictions', 'match.expertPicks'])->get();

        $aiStats = [
            'win_draw_loss' => ['total' => 0, 'won' => 0, 'rate' => 0.0],
            'gg' => ['total' => 0, 'won' => 0, 'rate' => 0.0],
            'over_2_5' => ['total' => 0, 'won' => 0, 'rate' => 0.0],
            'overall' => ['total' => 0, 'won' => 0, 'rate' => 0.0],
        ];

        $expertStats = [
            'win_draw_loss' => ['total' => 0, 'won' => 0, 'rate' => 0.0],
            'gg' => ['total' => 0, 'won' => 0, 'rate' => 0.0],
            'over_2_5' => ['total' => 0, 'won' => 0, 'rate' => 0.0],
            'overall' => ['total' => 0, 'won' => 0, 'rate' => 0.0],
        ];

        foreach ($settledResults as $result) {
            $hScore = $result->home_score;
            $aScore = $result->away_score;

            // Actual outcomes
            $actualWdl = $hScore > $aScore ? 'Home Win' : ($hScore === $aScore ? 'Draw' : 'Away Win');
            $actualGg = ($hScore > 0 && $aScore > 0) ? 'GG (Yes)' : 'NG (No)';
            $actualOver = ($hScore + $aScore) > 2.5 ? 'Over 2.5' : 'Under 2.5';

            // Check AI predictions
            foreach ($result->match->predictions as $pred) {
                $isWin = false;
                if ($pred->market === 'win_draw_loss' && $pred->pick === $actualWdl) {
                    $isWin = true;
                } elseif ($pred->market === 'gg' && $pred->pick === $actualGg) {
                    $isWin = true;
                } elseif ($pred->market === 'over_2_5' && $pred->pick === $actualOver) {
                    $isWin = true;
                }

                $m = $pred->market;
                $aiStats[$m]['total']++;
                $aiStats['overall']['total']++;
                if ($isWin) {
                    $aiStats[$m]['won']++;
                    $aiStats['overall']['won']++;
                }
            }

            // Check Expert picks
            foreach ($result->match->expertPicks as $pick) {
                $isWin = false;
                if ($pick->market === 'win_draw_loss' && $pick->pick === $actualWdl) {
                    $isWin = true;
                } elseif ($pick->market === 'gg' && $pick->pick === $actualGg) {
                    $isWin = true;
                } elseif ($pick->market === 'over_2_5' && $pick->pick === $actualOver) {
                    $isWin = true;
                }

                $m = $pick->market;
                $expertStats[$m]['total']++;
                $expertStats['overall']['total']++;
                if ($isWin) {
                    $expertStats[$m]['won']++;
                    $expertStats['overall']['won']++;
                }
            }
        }

        // Calculate win percentage rates
        foreach (['win_draw_loss', 'gg', 'over_2_5', 'overall'] as $k) {
            $aiStats[$k]['rate'] = $aiStats[$k]['total'] > 0 ? round(($aiStats[$k]['won'] / $aiStats[$k]['total']) * 100, 1) : 0.0;
            $expertStats[$k]['rate'] = $expertStats[$k]['total'] > 0 ? round(($expertStats[$k]['won'] / $expertStats[$k]['total']) * 100, 1) : 0.0;
        }

        return [
            'ai' => $aiStats,
            'expert' => $expertStats,
        ];
    }
}
