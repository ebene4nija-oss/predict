<?php

namespace App\Jobs;

use App\Models\Prediction;
use App\Support\MarketRegistry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class Ai5SelectionJob implements ShouldQueue
{
    use Queueable;

    /** How many picks the flagship list carries. */
    public const SIZE = 5;

    /**
     * Ceiling per market, so the hero stays cross-market.
     *
     * Markets do not share a probability range: first-half over 0.5 sits near
     * 75% and over 8.5 corners near 70% by construction, while a half-time win
     * rarely clears 45%. Ranking five slots on raw probability alone would hand
     * every one of them to the same market, every day.
     */
    public const MAX_PER_MARKET = 2;

    public function handle(): void
    {
        // Reset AI 5 status
        Prediction::query()->update(['is_ai5' => false]);

        $eligible = [];

        foreach (MarketRegistry::listed() as $market => $definition) {
            if ($definition->generated) {
                $eligible[$market] = MarketRegistry::threshold($market);
            }
        }

        if ($eligible === []) {
            return;
        }

        // A generous candidate pool: the caps below reject most of it, so the
        // limit has to leave room for lower-ranked picks in thinner markets.
        $candidates = Prediction::with('match')
            ->forUpcomingMatches()
            ->whereIn('market', array_keys($eligible))
            ->orderByDesc('probability')
            ->orderBy('id')
            ->limit(200)
            ->get()
            ->filter(fn (Prediction $p) => $p->probability >= ($eligible[$p->market] ?? 1.0));

        $selected = [];
        $perMarket = [];
        $seenMatches = [];

        foreach ($candidates as $prediction) {
            if (count($selected) >= self::SIZE) {
                break;
            }

            // One slot per fixture: the same match appearing three times is a
            // correlated bet, not five independent conviction picks.
            if (isset($seenMatches[$prediction->match_id])) {
                continue;
            }

            $used = $perMarket[$prediction->market] ?? 0;

            if ($used >= self::MAX_PER_MARKET) {
                continue;
            }

            $selected[] = $prediction->id;
            $perMarket[$prediction->market] = $used + 1;
            $seenMatches[$prediction->match_id] = true;
        }

        Prediction::whereIn('id', $selected)->update(['is_ai5' => true]);
    }
}
