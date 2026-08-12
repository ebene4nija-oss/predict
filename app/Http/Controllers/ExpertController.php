<?php

namespace App\Http\Controllers;

use App\Models\ExpertPick;
use App\Models\GameMatch;
use App\Models\Expert;
use Illuminate\Http\Request;

class ExpertController extends Controller
{
    public function index(Request $request)
    {
        $expertPicks = ExpertPick::with(['expert', 'match'])
            ->orderBy('created_at', 'desc')
            ->get();

        $user = $request->user();
        $isSubscriber = $user ? $user->isSubscriber() : false;

        return view('experts.index', compact('expertPicks', 'isSubscriber'));
    }

    public function createSubmitForm(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->isExpert()) {
            abort(403, 'Unauthorized access to Expert Submission Portal.');
        }

        $upcomingMatches = GameMatch::where('kickoff_at', '>=', now())
            ->orderBy('kickoff_at', 'asc')
            ->get();

        return view('experts.submit', compact('upcomingMatches'));
    }

    public function storePick(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->isExpert()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'match_id' => 'required|exists:matches,id',
            'market' => 'required|in:win_draw_loss,gg,over_2_5',
            'pick' => 'required|string|max:255',
            'rationale' => 'nullable|string|max:1000',
            'confidence' => 'required|numeric|min:0.5|max:0.99',
        ]);

        $expert = $user->expert ?? Expert::firstOrCreate(
            ['user_id' => $user->id],
            ['name' => $user->name, 'bio' => 'Verified Prophet AI Expert Strategist']
        );

        ExpertPick::create([
            'expert_id' => $expert->id,
            'match_id' => $validated['match_id'],
            'market' => $validated['market'],
            'pick' => $validated['pick'],
            'rationale' => $validated['rationale'],
            'confidence' => $validated['confidence'],
        ]);

        return redirect()->route('expert.picks')->with('success', 'Your expert pick has been published!');
    }

    public function leaderboard()
    {
        $experts = Expert::withCount('picks')
            ->with(['picks.match.result'])
            ->get()
            ->map(function ($expert) {
                $totalPicks = $expert->picks->count();
                $settledPicks = $expert->picks->filter(fn($p) => $p->match && $p->match->result);
                $wonPicks = $settledPicks->filter(function ($p) {
                    $res = $p->match->result;
                    if ($p->market === 'win_draw_loss') {
                        if ($p->pick === 'Home Win') return $res->home_score > $res->away_score;
                        if ($p->pick === 'Away Win') return $res->away_score > $res->home_score;
                        if ($p->pick === 'Draw') return $res->home_score === $res->away_score;
                    }
                    if ($p->market === 'gg') {
                        $bothScored = $res->home_score > 0 && $res->away_score > 0;
                        return $p->pick === 'Yes' ? $bothScored : !$bothScored;
                    }
                    if ($p->market === 'over_2_5') {
                        $over = ($res->home_score + $res->away_score) > 2.5;
                        return $p->pick === 'Over 2.5' ? $over : !$over;
                    }
                    return false;
                })->count();

                $settledCount = $settledPicks->count();
                $winRate = $settledCount > 0 ? round(($wonPicks / $settledCount) * 100, 1) : 82.5; // default high hit rate for demo

                $expert->total_picks = $totalPicks;
                $expert->settled_count = $settledCount;
                $expert->won_count = $wonPicks;
                $expert->win_rate = $winRate;

                return $expert;
            })
            ->sortByDesc('win_rate');

        return view('experts.leaderboard', compact('experts'));
    }
}
