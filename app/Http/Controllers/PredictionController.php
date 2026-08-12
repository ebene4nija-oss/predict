<?php

namespace App\Http\Controllers;

use App\Models\Prediction;
use Illuminate\Http\Request;

class PredictionController extends Controller
{
    public function topPicks(Request $request)
    {
        $market = $request->query('market', 'win_draw_loss');
        if (!in_array($market, ['win_draw_loss', 'gg', 'over_2_5'])) {
            $market = 'win_draw_loss';
        }

        $aiTop5 = Prediction::with('match')
            ->where('is_ai5', true)
            ->orderBy('probability', 'desc')
            ->get();

        $top10Picks = Prediction::with('match')
            ->where('market', $market)
            ->orderBy('probability', 'desc')
            ->take(10)
            ->get();

        $user = $request->user();
        $isSubscriber = $user ? $user->isSubscriber() : false;

        return view('predictions.top-picks', compact('market', 'aiTop5', 'top10Picks', 'isSubscriber'));
    }
}
