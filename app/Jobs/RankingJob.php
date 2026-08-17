<?php

namespace App\Jobs;

use App\Models\Prediction;
use App\Support\MarketRegistry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RankingJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        // Reset top 10 status
        Prediction::query()->update(['is_top10' => false]);

        // Only markets with a public list are ranked — is_top10 exists to fill
        // a Top 10 tab, and win_draw_loss has none.
        foreach (MarketRegistry::listed() as $market => $definition) {
            if (! $definition->generated) {
                continue;
            }

            // The threshold gates *promotion* to the public list. It is applied
            // here rather than in the engine, where it used to rewrite the
            // stored probability upward, and it is per-market: one global
            // figure cannot serve markets whose natural range differs by forty
            // points.
            $threshold = MarketRegistry::threshold($market);

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
