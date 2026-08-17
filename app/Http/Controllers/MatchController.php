<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Services\TrackRecordService;
use App\Support\MarketRegistry;

class MatchController extends Controller
{
    public function show(GameMatch $match, TrackRecordService $trackRecordService)
    {
        $match->load([
            // Listed markets only. win and win_draw_loss are two readings of
            // the same fixture and usually name the same side, so showing both
            // reads as the model saying it twice.
            'predictions' => fn ($query) => $query->whereIn('market', array_keys(MarketRegistry::listed())),
            'expertPicks.expert',
            'homeClub',
            'awayClub',
        ]);

        $stats = $trackRecordService->getAccuracyStats();
        $overallAiWinRate = $stats['ai']['overall']['rate'] ?? 68.4;

        return view('matches.show', compact('match', 'overallAiWinRate'));
    }
}
