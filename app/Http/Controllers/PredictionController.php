<?php

namespace App\Http\Controllers;

use App\Models\Prediction;
use App\Support\MarketOutcome;
use Illuminate\Http\Request;

class PredictionController extends Controller
{
    public function topPicks(Request $request)
    {
        $market = $request->query('market', 'win_draw_loss');
        if (! in_array($market, MarketOutcome::MARKETS, true)) {
            $market = 'win_draw_loss';
        }

        $aiTop5 = Prediction::with('match')
            ->where('is_ai5', true)
            ->forUpcomingMatches()
            ->orderBy('probability', 'desc')
            ->take(5)
            ->get();

        $top10Picks = Prediction::with('match')
            ->where('market', $market)
            ->forUpcomingMatches()
            ->orderBy('probability', 'desc')
            ->take(10)
            ->get();

        $user = $request->user();
        $isSubscriber = $user ? $user->isSubscriber() : false;

        return view('predictions.top-picks', compact('market', 'aiTop5', 'top10Picks', 'isSubscriber'));
    }
}
