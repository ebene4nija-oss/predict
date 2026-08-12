<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Services\TrackRecordService;

class MatchController extends Controller
{
    public function show(GameMatch $match, TrackRecordService $trackRecordService)
    {
        $match->load(['predictions', 'expertPicks.expert']);

        $stats = $trackRecordService->getAccuracyStats();
        $overallAiWinRate = $stats['ai']['overall']['rate'] ?? 68.4;

        return view('matches.show', compact('match', 'overallAiWinRate'));
    }
}
