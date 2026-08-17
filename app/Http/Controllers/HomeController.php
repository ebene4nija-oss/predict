<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\Post;
use App\Models\Prediction;
use App\Services\TrackRecordService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(TrackRecordService $trackRecordService)
    {
        // The home page has its own, narrower window. Fixtures beyond it keep
        // their pages, previews and sitemap entries — they are simply not
        // featured on the front page.
        $todayFixtures = GameMatch::with(['predictions', 'expertPicks.expert', 'homeClub', 'awayClub'])
            ->forHomeListing()
            ->orderBy('kickoff_at', 'asc')
            ->take(6)
            ->get();

        $latestPosts = Post::published()
            ->orderByDesc('published_at')
            ->take(3)
            ->get();

        $aiTop5 = Prediction::with('match')
            ->where('is_ai5', true)
            ->forUpcomingMatches()
            ->orderBy('probability', 'desc')
            ->take(5)
            ->get();

        $stats = $trackRecordService->getAccuracyStats();

        return view('home', compact('todayFixtures', 'aiTop5', 'stats', 'latestPosts'));
    }

    public function howAiWorks()
    {
        return view('how-ai-works');
    }
}
