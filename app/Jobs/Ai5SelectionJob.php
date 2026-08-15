<?php

namespace App\Jobs;

use App\Models\Prediction;
use App\Services\PredictionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class Ai5SelectionJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        // Reset AI 5 status
        Prediction::query()->update(['is_ai5' => false]);

        // Top 5 highest-probability picks across all markets, restricted to
        // fixtures that have not kicked off and to picks clearing the
        // publication threshold.
        $top5Ids = Prediction::forUpcomingMatches()
            ->where('probability', '>=', PredictionService::publishThreshold())
            ->orderByDesc('probability')
            ->orderBy('id')
            ->limit(5)
            ->pluck('id');

        Prediction::whereIn('id', $top5Ids)->update(['is_ai5' => true]);
    }
}
