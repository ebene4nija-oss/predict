<?php

namespace App\Http\Controllers;

use App\Models\Prediction;
use App\Models\Setting;
use App\Support\MarketRegistry;
use Illuminate\Http\Request;

class PredictionController extends Controller
{
    /** Ranked picks visible without a subscription, per list. */
    public const DEFAULT_FREE_PICKS = 3;

    public function topPicks(Request $request)
    {
        $market = MarketRegistry::resolve($request->query('market'));
        $definition = MarketRegistry::find($market);

        $aiTop5 = Prediction::with('match')
            ->where('is_ai5', true)
            ->forUpcomingMatches()
            ->orderBy('probability', 'desc')
            ->take(5)
            ->get();

        // A market the engine does not produce yet has no picks to fetch; the
        // view renders it as pending rather than as an empty leaderboard.
        $top10Picks = $definition?->generated
            ? Prediction::with('match')
                ->where('market', $market)
                ->forUpcomingMatches()
                ->orderBy('probability', 'desc')
                ->take(10)
                ->get()
            : collect();

        $user = $request->user();
        $isSubscriber = $user ? $user->isSubscriber() : false;

        return view('predictions.top-picks', [
            'market' => $market,
            'definition' => $definition,
            'markets' => MarketRegistry::listed(),
            'aiTop5' => $aiTop5,
            'top10Picks' => $top10Picks,
            'isSubscriber' => $isSubscriber,
            'freePicks' => self::freePicks(),
        ]);
    }

    /**
     * Tunable without a deploy, and clamped: a stray settings value must not be
     * able to unlock the whole list or hide it entirely.
     */
    public static function freePicks(): int
    {
        $configured = (int) Setting::get('free_pick_limit', self::DEFAULT_FREE_PICKS);

        return max(1, min(10, $configured ?: self::DEFAULT_FREE_PICKS));
    }
}
