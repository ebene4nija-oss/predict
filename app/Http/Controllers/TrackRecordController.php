<?php

namespace App\Http\Controllers;

use App\Models\Result;
use App\Services\TrackRecordService;
use Illuminate\Http\Request;

class TrackRecordController extends Controller
{
    public function index(Request $request, TrackRecordService $trackRecordService)
    {
        $stats = $trackRecordService->getAccuracyStats();

        $history = Result::with(['match.predictions', 'match.expertPicks.expert'])
            ->orderBy('settled_at', 'desc')
            ->get();

        return view('track-record', compact('stats', 'history'));
    }
}
