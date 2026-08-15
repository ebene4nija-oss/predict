<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\Prediction;
use App\Services\TrackRecordService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(TrackRecordService $trackRecordService)
    {
        $todayFixtures = GameMatch::with(['predictions', 'expertPicks.expert'])
            ->where('kickoff_at', '>=', now()->subHours(4))
            ->orderBy('kickoff_at', 'asc')
            ->take(6)
            ->get();

        $aiTop5 = Prediction::with('match')
            ->where('is_ai5', true)
            ->forUpcomingMatches()
            ->orderBy('probability', 'desc')
            ->take(5)
            ->get();

        $stats = $trackRecordService->getAccuracyStats();

        return view('home', compact('todayFixtures', 'aiTop5', 'stats'));
    }

    public function howAiWorks()
    {
        return view('how-ai-works');
    }
}
