<?php

namespace App\Http\Controllers;

use App\Models\ExpertPick;
use App\Models\GameMatch;
use App\Models\Expert;
use App\Support\MarketOutcome;
use App\Support\MarketRegistry;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpertController extends Controller
{
    public function index(Request $request)
    {
        // The id tiebreaker keeps the order total: picks created in the same
        // second would otherwise sort arbitrarily, which both scrambles the
        // free-sample selection and lets paginated rows repeat or go missing.
        $expertPicks = ExpertPick::with(['expert', 'match'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20);

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
            // Only markets the platform actually settles — a pick in a market
            // with no result data could never be graded.
            'market' => ['required', Rule::in(MarketRegistry::generatedKeys())],
            'pick' => 'required|string|max:255',
            'rationale' => 'nullable|string|max:1000',
            'confidence' => 'required|numeric|min:0.5|max:0.99',
        ]);

        $expert = $user->expert ?? Expert::firstOrCreate(
            ['user_id' => $user->id],
            ['name' => $user->name, 'bio' => 'Verified Guaranteed Correct Expert Strategist']
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
                // Unscoreable free-text picks are left out of the denominator
                // too, so the leaderboard agrees with the track record.
                $settledPicks = $expert->picks->filter(
                    fn ($p) => $p->match
                        && $p->match->result
                        && MarketOutcome::isGradeable($p->market, $p->pick)
                        // Markets whose settle data has not arrived are left
                        // out of the denominator as well, so the leaderboard
                        // agrees with the track record.
                        && MarketOutcome::isDeterminable($p->market, $p->match->result->gradingContext())
                );

                $wonCount = $settledPicks->filter(fn ($p) => MarketOutcome::isWinningPick(
                    $p->market,
                    $p->pick,
                    $p->match->result->home_score,
                    $p->match->result->away_score,
                    $p->match->result->gradingContext(),
                ))->count();

                $settledCount = $settledPicks->count();

                $expert->total_picks = $expert->picks->count();
                $expert->settled_count = $settledCount;
                $expert->won_count = $wonCount;

                // Null, not a flattering placeholder: an expert with nothing
                // settled yet has no record, and publishing an invented hit
                // rate on a betting site is not something to paper over.
                $expert->win_rate = $settledCount > 0
                    ? round(($wonCount / $settledCount) * 100, 1)
                    : null;

                return $expert;
            })
            // Unrated experts sort last rather than above proven ones.
            ->sortByDesc(fn ($expert) => $expert->win_rate ?? -1)
            ->values();

        return view('experts.leaderboard', compact('experts'));
    }
}
