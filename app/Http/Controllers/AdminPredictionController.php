<?php

namespace App\Http\Controllers;

use App\Models\Prediction;
use App\Models\GameMatch;
use Illuminate\Http\Request;

class AdminPredictionController extends Controller
{
    public function index(Request $request)
    {
        $market = $request->get('market', 'win_draw_loss');

        $predictions = Prediction::with('match')
            ->where('market', $market)
            ->orderBy('probability', 'desc')
            ->paginate(20);

        return view('admin.predictions.index', compact('predictions', 'market'));
    }

    public function update(Request $request, Prediction $prediction)
    {
        $validated = $request->validate([
            'pick' => 'required|string|max:100',
            'probability' => 'required|numeric|min:0.01|max:0.99',
            'rationale' => 'nullable|string',
            'is_top10' => 'nullable|boolean',
            'is_ai5' => 'nullable|boolean',
        ]);

        $prediction->update([
            'pick' => $validated['pick'],
            'probability' => $validated['probability'],
            'rationale' => $validated['rationale'] ?? $prediction->rationale,
            'is_top10' => $request->boolean('is_top10'),
            'is_ai5' => $request->boolean('is_ai5'),
        ]);

        return redirect()->route('admin.predictions.index', ['market' => $prediction->market])
            ->with('success', "Prediction #{$prediction->id} updated successfully.");
    }
}
