<?php

namespace App\Jobs;

use App\Models\Prediction;
use App\Services\PredictionService;
use App\Support\MarketOutcome;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RankingJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        // Reset top 10 status
        Prediction::query()->update(['is_top10' => false]);

        // The confidence threshold gates *promotion* to the public list. It is
        // deliberately applied here rather than in the engine, where it used to
        // rewrite the stored probability upward.
        $threshold = PredictionService::publishThreshold();

        foreach (MarketOutcome::MARKETS as $market) {
            // Only rank fixtures that have not kicked off — a finished match
            // with a high probability is not a pick anyone can act on.
            $topIds = Prediction::forUpcomingMatches()
                ->where('market', $market)
                ->where('probability', '>=', $threshold)
                ->orderByDesc('probability')
                ->orderBy('id')
                ->limit(10)
                ->pluck('id');

            Prediction::whereIn('id', $topIds)->update(['is_top10' => true]);
        }
    }
}
