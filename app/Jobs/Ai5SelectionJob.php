<?php

namespace App\Jobs;

use App\Models\Prediction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class Ai5SelectionJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        // Reset AI 5 status
        Prediction::query()->update(['is_ai5' => false]);

        // Get top 5 highest probability picks across all markets
        $top5Ids = Prediction::orderBy('probability', 'desc')
            ->limit(5)
            ->pluck('id');

        Prediction::whereIn('id', $top5Ids)->update(['is_ai5' => true]);
    }
}
