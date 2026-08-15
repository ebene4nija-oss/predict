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

        // Paginated: this grows with every settled match and previously loaded
        // the entire history, with relations, on every page view.
        $history = Result::with(['match.predictions', 'match.expertPicks.expert'])
            ->orderByDesc('settled_at')
            ->orderByDesc('id')
            ->paginate(25);

        return view('track-record', compact('stats', 'history'));
    }
}
