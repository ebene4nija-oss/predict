<?php

namespace App\Jobs;

use App\Models\Prediction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RankingJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        // Reset top 10 status
        Prediction::query()->update(['is_top10' => false]);

        $markets = ['win_draw_loss', 'gg', 'over_2_5'];

        foreach ($markets as $market) {
            $topIds = Prediction::where('market', $market)
                ->orderBy('probability', 'desc')
                ->limit(10)
                ->pluck('id');

            Prediction::whereIn('id', $topIds)->update(['is_top10' => true]);
        }
    }
}
