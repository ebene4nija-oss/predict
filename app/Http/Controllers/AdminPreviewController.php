<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Services\PreviewGenerationService;
use App\Support\MarketRegistry;
use Illuminate\Http\Request;

class AdminPreviewController extends Controller
{
    /**
     * Display match previews manager index.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $filter = $request->query('filter', 'all');

        $matches = GameMatch::query()
            ->searchMatches($search)
            ->withPreviewFilter($filter)
            ->with(['predictions' => fn ($q) => $q->whereIn('market', array_keys(MarketRegistry::listed())), 'result'])
            ->orderBy('kickoff_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $counts = [
            'total' => GameMatch::count(),
            'with_preview' => GameMatch::whereNotNull('preview_text')->where('preview_text', '!=', '')->count(),
            'missing' => GameMatch::where(fn ($q) => $q->whereNull('preview_text')->orWhere('preview_text', ''))->count(),
            'gemini' => GameMatch::where('preview_source', GameMatch::PREVIEW_SOURCE_MODEL)->count(),
            'custom' => GameMatch::where('is_preview_custom', true)->count(),
            'fallback' => GameMatch::where('preview_source', GameMatch::PREVIEW_SOURCE_FALLBACK)->count(),
            'published' => GameMatch::where('preview_status', GameMatch::PREVIEW_STATUS_PUBLISHED)->count(),
            'draft' => GameMatch::where('preview_status', GameMatch::PREVIEW_STATUS_DRAFT)->count(),
        ];

        $aiConfigured = app(PreviewGenerationService::class)->isConfigured();

        return view('admin.previews.index', compact('matches', 'counts', 'search', 'filter', 'aiConfigured'));
    }

    /**
     * Show match preview and SEO editor.
     */
    public function edit(GameMatch $match)
    {
        $match->load([
            'predictions' => fn ($query) => $query->whereIn('market', array_keys(MarketRegistry::listed())),
            'homeClub',
            'awayClub',
        ]);

        $aiConfigured = app(PreviewGenerationService::class)->isConfigured();

        return view('admin.previews.edit', compact('match', 'aiConfigured'));
    }

    /**
     * Update match preview narrative and SEO attributes.
     */
    public function update(Request $request, GameMatch $match)
    {
        $validated = $request->validate([
            'preview_headline' => 'nullable|string|max:255',
            'preview_text' => 'nullable|string',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'seo_keywords' => 'nullable|string|max:500',
            'preview_status' => 'required|in:published,draft',
            'is_preview_custom' => 'nullable|boolean',
        ]);

        $match->update([
            'preview_headline' => $validated['preview_headline'] ?? null,
            'preview_text' => $validated['preview_text'] ?? null,
            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,
            'seo_keywords' => $validated['seo_keywords'] ?? null,
            'preview_status' => $validated['preview_status'],
            'is_preview_custom' => $request->boolean('is_preview_custom', true),
            'preview_source' => $match->preview_source ?: GameMatch::PREVIEW_SOURCE_CUSTOM,
        ]);

        return redirect()->route('admin.previews.edit', $match)->with('success', 'Match preview and SEO metadata saved successfully.');
    }

    /**
     * On-demand AI preview generation for a specific match fixture.
     */
    public function generate(Request $request, GameMatch $match, PreviewGenerationService $previewService)
    {
        $validated = $request->validate([
            'instruction' => 'nullable|string|max:500',
        ]);

        $previewService->generatePreview($match, $validated['instruction'] ?? null, true);

        return redirect()->route('admin.previews.edit', $match)->with('success', 'Comprehensive SEO match preview generated successfully with Gemini AI.');
    }

    /**
     * Bulk generate AI match previews for upcoming fixtures missing previews.
     */
    public function bulkGenerate(Request $request, PreviewGenerationService $previewService)
    {
        $upcomingMatches = GameMatch::where('kickoff_at', '>=', now())
            ->where(function ($q) {
                $q->whereNull('preview_text')
                    ->orWhere('preview_text', '')
                    ->orWhere('preview_source', GameMatch::PREVIEW_SOURCE_FALLBACK);
            })
            ->where('is_preview_custom', false)
            ->limit(20)
            ->get();

        if ($upcomingMatches->isEmpty()) {
            return back()->with('info', 'No upcoming matches currently require preview generation.');
        }

        $generated = 0;
        foreach ($upcomingMatches as $match) {
            $previewService->generatePreview($match, null, false);
            $generated++;
        }

        return back()->with('success', "Successfully generated {$generated} match preview(s) for upcoming fixtures.");
    }

    /**
     * Toggle preview publication status between published and draft.
     */
    public function togglePublish(GameMatch $match)
    {
        $newStatus = $match->preview_status === GameMatch::PREVIEW_STATUS_DRAFT
            ? GameMatch::PREVIEW_STATUS_PUBLISHED
            : GameMatch::PREVIEW_STATUS_DRAFT;

        $match->update(['preview_status' => $newStatus]);

        $label = $newStatus === GameMatch::PREVIEW_STATUS_PUBLISHED ? 'published' : 'moved to draft';

        return back()->with('success', "Preview for {$match->home_team} vs {$match->away_team} is now {$label}.");
    }
}
