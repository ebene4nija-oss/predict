<?php

namespace App\Http\Controllers;

use App\Models\Prediction;
use App\Services\PredictionService;
use App\Support\MarketRegistry;
use Illuminate\Http\Request;

class AdminPredictionController extends Controller
{
    public function index(Request $request)
    {
        $requested = (string) $request->get('market', '');

        // Unlike the public list, admin can inspect every registered market —
        // including win_draw_loss, which has no public tab of its own.
        $market = MarketRegistry::has($requested) ? $requested : MarketRegistry::DEFAULT_KEY;

        $predictions = Prediction::with('match')
            ->where('market', $market)
            ->orderBy('probability', 'desc')
            ->paginate(20);

        $markets = MarketRegistry::all();

        return view('admin.predictions.index', compact('predictions', 'market', 'markets'));
    }

    public function update(Request $request, Prediction $prediction, PredictionService $predictions)
    {
        $prediction->loadMissing('match');

        // A published record that can be edited after kickoff is not a record.
        // Editing a started fixture is refused outright rather than logged.
        if ($prediction->match && $predictions->isLocked($prediction->match)) {
            return redirect()->route('admin.predictions.index', ['market' => $prediction->market])
                ->with('error', 'This fixture has already kicked off — its prediction is locked and cannot be edited.');
        }

        $validated = $request->validate([
            'pick' => 'required|string|max:100',
            'probability' => 'required|numeric|min:0.01|max:0.99',
            'rationale' => 'nullable|string',
            'odds' => 'nullable|numeric|min:1.01|max:1000',
            'is_top10' => 'nullable|boolean',
            'is_ai5' => 'nullable|boolean',
        ]);

        $prediction->update([
            'pick' => $validated['pick'],
            'probability' => $validated['probability'],
            'rationale' => $validated['rationale'] ?? $prediction->rationale,
            'odds' => $validated['odds'] ?? $prediction->odds,
            'source' => 'manual_override',
            'published_at' => $prediction->published_at ?? now(),
            'is_top10' => $request->boolean('is_top10'),
            'is_ai5' => $request->boolean('is_ai5'),
        ]);

        return redirect()->route('admin.predictions.index', ['market' => $prediction->market])
            ->with('success', "Prediction #{$prediction->id} updated successfully.");
    }
}
