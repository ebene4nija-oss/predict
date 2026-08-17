<?php

namespace App\Http\Controllers;

use App\Models\Expert;
use App\Models\ExpertPick;
use App\Models\GameMatch;
use App\Support\MarketRegistry;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminExpertController extends Controller
{
    public function index()
    {
        $experts = Expert::withCount('picks')->get();
        $picks = ExpertPick::with(['expert', 'match'])->latest()->paginate(15);

        return view('admin.experts.index', compact('experts', 'picks'));
    }

    public function storeExpert(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'bio' => 'nullable|string',
            'photo_path' => 'nullable|string|url',
        ]);

        Expert::create($validated);

        return redirect()->route('admin.experts.index')->with('success', "Expert '{$validated['name']}' created successfully.");
    }

    public function updateExpert(Request $request, Expert $expert)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'bio' => 'nullable|string',
            'photo_path' => 'nullable|string|url',
        ]);

        $expert->update($validated);

        return redirect()->route('admin.experts.index')->with('success', "Expert '{$expert->name}' updated.");
    }

    public function destroyExpert(Expert $expert)
    {
        $expert->delete();
        return redirect()->route('admin.experts.index')->with('success', 'Expert profile deleted.');
    }

    public function storePick(Request $request)
    {
        $validated = $request->validate([
            'expert_id' => 'required|exists:experts,id',
            'match_id' => 'required|exists:matches,id',
            'market' => ['required', Rule::in(MarketRegistry::generatedKeys())],
            'pick' => 'required|string',
            'rationale' => 'nullable|string',
            'confidence' => 'required|numeric|min:0.50|max:0.99',
        ]);

        ExpertPick::create($validated);

        return redirect()->route('admin.experts.index')->with('success', 'Expert pick submitted successfully.');
    }

    public function destroyPick(ExpertPick $pick)
    {
        $pick->delete();
        return redirect()->route('admin.experts.index')->with('success', 'Expert pick deleted.');
    }
}
