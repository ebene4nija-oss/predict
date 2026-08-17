<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminTeamController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));

        $teams = Team::query()
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->withCount(['homeMatches', 'awayMatches'])
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        $counts = [
            'total' => Team::count(),
            'with_crest' => Team::whereNotNull('crest_url')->orWhereNotNull('custom_crest_url')->count(),
            'unlinked_matches' => GameMatch::whereNull('home_team_id')->orWhereNull('away_team_id')->count(),
        ];

        return view('admin.teams.index', compact('teams', 'counts', 'search'));
    }

    public function update(Request $request, Team $team)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:255',
            'tla' => 'nullable|string|max:8',
            'custom_crest_url' => 'nullable|url|max:500',
        ]);

        $team->update($validated);

        return back()->with('success', "Crest and details updated for {$team->name}.");
    }

    /**
     * Attach existing fixtures to club records.
     *
     * Fixtures ingested before teams existed hold only the display strings.
     * This creates a club per distinct name and links both sides, so those
     * matches stop rendering without a crest. Crest URLs arrive on the next
     * ingestion run, which adopts these rows rather than duplicating them.
     */
    public function backfill()
    {
        $names = DB::table('matches')
            ->selectRaw('home_team as name')
            ->union(DB::table('matches')->selectRaw('away_team as name'))
            ->pluck('name')
            ->filter()
            ->unique();

        $ids = [];

        foreach ($names as $name) {
            $ids[$name] = Team::resolve(null, null, $name)->id;
        }

        $linked = 0;

        // Grouped: chunkById appends its own `id >` clause with AND, which
        // would bind tighter than a bare OR and select the wrong rows.
        GameMatch::where(fn ($q) => $q->whereNull('home_team_id')->orWhereNull('away_team_id'))
            ->chunkById(200, function ($matches) use ($ids, &$linked) {
                foreach ($matches as $match) {
                    $match->forceFill([
                        'home_team_id' => $match->home_team_id ?? ($ids[$match->home_team] ?? null),
                        'away_team_id' => $match->away_team_id ?? ($ids[$match->away_team] ?? null),
                    ])->save();
                    $linked++;
                }
            });

        return back()->with('success', "Linked {$linked} fixtures to ".count($ids).' club records.');
    }
}
