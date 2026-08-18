<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\Prediction;
use App\Services\TrackRecordService;
use App\Support\MarketRegistry;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    /**
     * Display a comprehensive listing of all upcoming match previews & predictions.
     */
    public function index(Request $request, TrackRecordService $trackRecordService)
    {
        $search = trim((string) $request->get('search', ''));
        $selectedLeague = trim((string) $request->get('league', ''));
        $timeFilter = (string) $request->get('time', 'all'); // 'today', 'tomorrow', 'weekend', 'all'
        $status = (string) $request->get('status', 'upcoming'); // 'upcoming', 'finished', 'all'

        $query = GameMatch::with([
            'homeClub',
            'awayClub',
            'predictions' => fn ($q) => $q->whereIn('market', array_keys(MarketRegistry::listed()))->orderBy('probability', 'desc'),
            'expertPicks.expert',
            'result',
        ]);

        // Status & Date filtering
        if ($status === 'finished') {
            $query->where('kickoff_at', '<', now()->subHours(3))
                ->orderByDesc('kickoff_at');
        } else {
            // Default upcoming
            if ($status === 'upcoming') {
                $query->where('kickoff_at', '>=', now()->subHours(3));
            }

            if ($timeFilter === 'today') {
                $query->whereBetween('kickoff_at', [now()->startOfDay(), now()->endOfDay()]);
            } elseif ($timeFilter === 'tomorrow') {
                $query->whereBetween('kickoff_at', [now()->addDay()->startOfDay(), now()->addDay()->endOfDay()]);
            } elseif ($timeFilter === 'weekend') {
                $saturday = now()->next(\Carbon\Carbon::SATURDAY)->startOfDay();
                $sunday = (clone $saturday)->addDay()->endOfDay();
                $query->whereBetween('kickoff_at', [$saturday, $sunday]);
            }

            $query->orderBy('kickoff_at', 'asc');
        }

        // League filtering
        if (filled($selectedLeague) && $selectedLeague !== 'all') {
            $query->where('league', $selectedLeague);
        }

        // Search filtering (team or league)
        if (filled($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('home_team', 'like', "%{$search}%")
                    ->orWhere('away_team', 'like', "%{$search}%")
                    ->orWhere('league', 'like', "%{$search}%");
            });
        }

        $matches = $query->paginate(12)->withQueryString();

        // Get list of all available leagues for filtering pills
        $availableLeagues = GameMatch::where('kickoff_at', '>=', now()->subHours(3))
            ->selectRaw('league, COUNT(*) as count')
            ->groupBy('league')
            ->orderByDesc('count')
            ->get();

        // AI Top 5 picks for SportyBet booking slip & showcase
        $aiTop5 = Prediction::with(['match.homeClub', 'match.awayClub'])
            ->where('is_ai5', true)
            ->forUpcomingMatches()
            ->orderBy('probability', 'desc')
            ->take(5)
            ->get();

        $stats = $trackRecordService->getAccuracyStats();
        $overallAiWinRate = $stats['ai']['overall']['rate'] ?? 68.4;
        $totalUpcomingMatches = GameMatch::where('kickoff_at', '>=', now()->subHours(3))->count();

        return view('matches.index', compact(
            'matches',
            'availableLeagues',
            'aiTop5',
            'search',
            'selectedLeague',
            'timeFilter',
            'status',
            'overallAiWinRate',
            'totalUpcomingMatches'
        ));
    }

    /**
     * Display detailed match preview, Poisson matrix, odds, and expert picks.
     */
    public function show(GameMatch $match, TrackRecordService $trackRecordService, ?string $slug = null)
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

        $user = request()->user();
        $isSubscriber = $user ? ($user->isSubscriber() || $user->isAdmin()) : false;

        return view('matches.show', compact('match', 'overallAiWinRate', 'isSubscriber'));
    }
}
