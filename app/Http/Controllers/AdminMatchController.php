<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\Result;
use App\Services\PredictionService;
use App\Services\PreviewGenerationService;
use App\Services\TrackRecordService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminMatchController extends Controller
{
    public function index()
    {
        $matches = GameMatch::with(['predictions', 'result', 'expertPicks'])
            ->orderBy('kickoff_at', 'desc')
            ->paginate(15);

        return view('admin.matches.index', compact('matches'));
    }

    public function create()
    {
        return view('admin.matches.create');
    }

    public function store(Request $request, PredictionService $predictionService, PreviewGenerationService $previewService)
    {
        $validated = $request->validate([
            'home_team' => 'required|string|max:255',
            'away_team' => 'required|string|max:255',
            'league' => 'required|string|max:255',
            'kickoff_at' => 'required|date',
            'home_gf' => 'nullable|numeric|min:0',
            'home_ga' => 'nullable|numeric|min:0',
            'away_gf' => 'nullable|numeric|min:0',
            'away_ga' => 'nullable|numeric|min:0',
            'h2h_summary' => 'nullable|string',
            'injury_notes' => 'nullable|string',
        ]);

        $match = GameMatch::create([
            'home_team' => $validated['home_team'],
            'away_team' => $validated['away_team'],
            'league' => $validated['league'],
            'kickoff_at' => Carbon::parse($validated['kickoff_at']),
            'home_form' => ['gf' => (float)($validated['home_gf'] ?? 1.8), 'ga' => (float)($validated['home_ga'] ?? 1.0)],
            'away_form' => ['gf' => (float)($validated['away_gf'] ?? 1.2), 'ga' => (float)($validated['away_ga'] ?? 1.5)],
            // Left null when not supplied. Defaulting these to "Recent balanced
            // form" and "No major absences reported" asserted facts nobody had
            // checked, and the preview generator then published them as though
            // they had been.
            'h2h_summary' => $validated['h2h_summary'] ?? null,
            'injury_notes' => $validated['injury_notes'] ?? null,
        ]);

        // Generate Claude predictions & Gemini preview automatically
        $predictionService->calculateAndStore($match);
        $previewService->generatePreview($match);

        return redirect()->route('admin.matches.index')->with('success', 'Match fixture created and AI pipeline triggered successfully!');
    }

    public function edit(GameMatch $match)
    {
        return view('admin.matches.edit', compact('match'));
    }

    public function update(Request $request, GameMatch $match)
    {
        $validated = $request->validate([
            'home_team' => 'required|string|max:255',
            'away_team' => 'required|string|max:255',
            'league' => 'required|string|max:255',
            'kickoff_at' => 'required|date',
            'h2h_summary' => 'nullable|string',
            'injury_notes' => 'nullable|string',
            'preview_text' => 'nullable|string',
        ]);

        $match->update([
            'home_team' => $validated['home_team'],
            'away_team' => $validated['away_team'],
            'league' => $validated['league'],
            'kickoff_at' => Carbon::parse($validated['kickoff_at']),
            'h2h_summary' => $validated['h2h_summary'],
            'injury_notes' => $validated['injury_notes'],
            'preview_text' => $validated['preview_text'],
        ]);

        return redirect()->route('admin.matches.index')->with('success', 'Fixture details updated successfully!');
    }

    public function destroy(GameMatch $match)
    {
        $match->delete();
        return redirect()->route('admin.matches.index')->with('success', 'Match fixture deleted successfully.');
    }

    public function showSettleForm(GameMatch $match)
    {
        $match->load('result');
        return view('admin.matches.settle', compact('match'));
    }

    public function settleResult(Request $request, GameMatch $match, TrackRecordService $trackRecordService)
    {
        $validated = $request->validate([
            'home_score' => 'required|integer|min:0|max:99',
            'away_score' => 'required|integer|min:0|max:99',
            // Optional: a fixture may be settled without one. Capped at the
            // full-time score because a side cannot un-score after the break,
            // and a typo here would grade the first-half markets wrongly.
            'ht_home_score' => 'nullable|integer|min:0|lte:home_score',
            'ht_away_score' => 'nullable|integer|min:0|lte:away_score',
        ], [
            'ht_home_score.lte' => 'The half-time home score cannot exceed the full-time home score.',
            'ht_away_score.lte' => 'The half-time away score cannot exceed the full-time away score.',
        ]);

        $h = (int) $validated['home_score'];
        $a = (int) $validated['away_score'];

        // Both halves of the half-time score or neither: one alone settles
        // nothing and would leave the markets half-graded.
        $htHome = $validated['ht_home_score'] ?? null;
        $htAway = $validated['ht_away_score'] ?? null;
        $hasHalfTime = $htHome !== null && $htAway !== null;

        Result::updateOrCreate(
            ['match_id' => $match->id],
            [
                'home_score' => $h,
                'away_score' => $a,
                'ht_home_score' => $hasHalfTime ? (int) $htHome : null,
                'ht_away_score' => $hasHalfTime ? (int) $htAway : null,
                // actual_outcome is derived on save; see Result::booted().
                'settled_at' => now(),
            ]
        );

        // Published accuracy figures are cached; settling a match changes them.
        $trackRecordService->flush();

        return redirect()->route('admin.matches.index')->with('success', "Match result settled: {$match->home_team} {$h} - {$a} {$match->away_team}. Public track record updated!");
    }
}
