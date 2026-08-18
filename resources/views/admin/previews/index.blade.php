@extends('layouts.app')

@section('title', 'Match Previews & SEO Manager — Guaranteed Correct Admin')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <!-- Admin Navigation Bar -->
    @include('admin.partials.nav')

    <!-- Flash Message Banner -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm flex items-center justify-between shadow-lg">
            <div class="flex items-center space-x-2.5">
                <svg class="w-5 h-5 text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-emerald-200 font-bold px-2">&times;</button>
        </div>
    @endif

    @if(session('info'))
        <div class="p-4 rounded-2xl bg-sky-500/10 border border-sky-500/30 text-sky-400 text-sm flex items-center justify-between shadow-lg">
            <div class="flex items-center space-x-2.5">
                <svg class="w-5 h-5 text-sky-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ session('info') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-sky-400 hover:text-sky-200 font-bold px-2">&times;</button>
        </div>
    @endif

    <!-- Page Header & Action Bar -->
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-sky-500/10 border border-sky-500/20 text-sky-400 text-xs font-bold font-mono mb-2">
                <span>SEO & EDITORIAL ENGINE</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">Match Previews & SEO Manager</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">Manage high-converting tactical match previews, automated Gemini AI copy, and search engine metadata.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <form action="{{ route('admin.previews.bulk-generate') }}" method="POST" onsubmit="return confirm('Generate AI previews for upcoming fixtures that are missing previews or on fallback?');">
                @csrf
                <button type="submit" class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-indigo-600 hover:from-sky-400 hover:to-indigo-500 text-white text-xs font-extrabold shadow-lg shadow-sky-500/25 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <span>⚡ Bulk Generate AI Previews</span>
                </button>
            </form>

            <a href="{{ route('admin.matches.create') }}" class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold border border-slate-700 transition-all">
                <span>+ New Fixture</span>
            </a>
        </div>
    </div>

    <!-- Metric KPI Cards Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
        <div class="p-4 rounded-2xl glass-panel border border-slate-800 space-y-1">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Matches</div>
            <div class="text-2xl font-black text-white font-mono">{{ $counts['total'] }}</div>
            <div class="text-[10px] text-slate-500">In database</div>
        </div>

        <div class="p-4 rounded-2xl glass-panel border border-slate-800 space-y-1">
            <div class="text-[11px] font-bold text-emerald-400 uppercase tracking-wider">With Preview</div>
            <div class="text-2xl font-black text-emerald-400 font-mono">{{ $counts['with_preview'] }}</div>
            <div class="text-[10px] text-slate-500">{{ $counts['total'] > 0 ? round(($counts['with_preview'] / $counts['total']) * 100) : 0 }}% coverage</div>
        </div>

        <div class="p-4 rounded-2xl glass-panel border border-slate-800 space-y-1">
            <div class="text-[11px] font-bold text-amber-400 uppercase tracking-wider">Missing</div>
            <div class="text-2xl font-black text-amber-400 font-mono">{{ $counts['missing'] }}</div>
            <div class="text-[10px] text-slate-500">Need generation</div>
        </div>

        <div class="p-4 rounded-2xl glass-panel border border-slate-800 space-y-1">
            <div class="text-[11px] font-bold text-sky-400 uppercase tracking-wider">Gemini AI</div>
            <div class="text-2xl font-black text-sky-400 font-mono">{{ $counts['gemini'] }}</div>
            <div class="text-[10px] text-slate-500">AI generated</div>
        </div>

        <div class="p-4 rounded-2xl glass-panel border border-slate-800 space-y-1">
            <div class="text-[11px] font-bold text-purple-400 uppercase tracking-wider">Custom Locked</div>
            <div class="text-2xl font-black text-purple-400 font-mono">{{ $counts['custom'] }}</div>
            <div class="text-[10px] text-slate-500">Human curated</div>
        </div>

        <div class="p-4 rounded-2xl glass-panel border border-slate-800 space-y-1">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Drafts</div>
            <div class="text-2xl font-black text-slate-300 font-mono">{{ $counts['draft'] }}</div>
            <div class="text-[10px] text-slate-500">Unpublished</div>
        </div>
    </div>

    <!-- Filter Bar & Search Form -->
    <div class="p-4 rounded-3xl glass-panel border border-slate-800 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4 shadow-xl">
        <!-- Filter Tabs -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0">
            @php
                $filterTabs = [
                    'all' => 'All Matches (' . $counts['total'] . ')',
                    'has_preview' => 'Has Preview (' . $counts['with_preview'] . ')',
                    'missing' => 'Missing (' . $counts['missing'] . ')',
                    'gemini' => 'Gemini AI (' . $counts['gemini'] . ')',
                    'custom' => 'Custom (' . $counts['custom'] . ')',
                    'fallback' => 'Fallback (' . $counts['fallback'] . ')',
                    'draft' => 'Drafts (' . $counts['draft'] . ')',
                ];
            @endphp

            @foreach($filterTabs as $key => $label)
                <a href="{{ route('admin.previews.index', ['filter' => $key, 'search' => $search]) }}"
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all {{ $filter === $key ? 'bg-sky-500 text-slate-950 font-black shadow-md shadow-sky-500/20' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <!-- Search Input Form -->
        <form method="GET" action="{{ route('admin.previews.index') }}" class="flex items-center space-x-2">
            <input type="hidden" name="filter" value="{{ $filter }}">
            <div class="relative flex-1 sm:w-64">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search team or league..."
                       class="w-full bg-slate-900/90 border border-slate-700 text-white rounded-xl pl-9 pr-4 py-2 text-xs focus:border-sky-400 focus:ring-1 focus:ring-sky-400 focus:outline-none transition-all placeholder-slate-500">
                <svg class="w-4 h-4 text-slate-500 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            @if($search)
                <a href="{{ route('admin.previews.index', ['filter' => $filter]) }}" class="px-2.5 py-2 rounded-xl bg-slate-800 text-slate-400 hover:text-white text-xs font-bold">
                    Clear
                </a>
            @endif
            <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-sky-400 text-xs font-bold border border-slate-700 transition-all">
                Search
            </button>
        </form>
    </div>

    <!-- Match Previews Table -->
    <div class="rounded-3xl glass-panel border border-slate-800 overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300 border-collapse">
                <thead>
                    <tr class="bg-slate-900/80 border-b border-slate-800 text-xs uppercase font-bold text-slate-400">
                        <th class="py-4 px-4 sm:px-6">Fixture & League</th>
                        <th class="py-4 px-4 sm:px-6">Kickoff</th>
                        <th class="py-4 px-4 sm:px-6">Preview Source & Status</th>
                        <th class="py-4 px-4 sm:px-6">Content & SEO Health</th>
                        <th class="py-4 px-4 sm:px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($matches as $match)
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <!-- Fixture & League -->
                            <td class="py-4 px-4 sm:px-6">
                                <div class="space-y-1">
                                    <div class="flex items-center space-x-2">
                                        <span class="font-extrabold text-white text-sm hover:text-sky-400 transition-colors">
                                            <a href="{{ route('admin.previews.edit', $match) }}">
                                                {{ $match->home_team }} <span class="text-slate-500 font-normal">vs</span> {{ $match->away_team }}
                                            </a>
                                        </span>
                                    </div>
                                    <div class="flex items-center space-x-2 text-xs text-slate-400">
                                        <span class="px-2 py-0.5 rounded-md bg-slate-900 border border-slate-800 text-[10px] font-semibold text-slate-300">
                                            {{ $match->league }}
                                        </span>
                                        <span class="font-mono text-[10px] text-slate-500">#{{ $match->id }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Kickoff -->
                            <td class="py-4 px-4 sm:px-6">
                                <div class="font-mono text-xs text-slate-300 font-medium">
                                    {{ $match->kickoff_at ? $match->kickoff_at->format('M d, Y') : 'TBD' }}
                                </div>
                                <div class="font-mono text-[11px] text-slate-500">
                                    {{ $match->kickoff_at ? $match->kickoff_at->format('H:i') . ' UTC' : '' }}
                                </div>
                            </td>

                            <!-- Preview Source & Status -->
                            <td class="py-4 px-4 sm:px-6">
                                <div class="flex flex-col items-start gap-1.5">
                                    @if(blank($match->preview_text))
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 mr-1.5"></span>
                                            Missing Preview
                                        </span>
                                    @elseif($match->isCustomPreview())
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-purple-500/10 text-purple-400 border border-purple-500/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-purple-400 mr-1.5"></span>
                                            Custom / Curated
                                        </span>
                                    @elseif($match->preview_source === \App\Models\GameMatch::PREVIEW_SOURCE_MODEL)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-sky-500/10 text-sky-400 border border-sky-500/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-sky-400 mr-1.5"></span>
                                            Gemini AI
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-700/50 text-slate-300 border border-slate-600">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400 mr-1.5"></span>
                                            Fallback Template
                                        </span>
                                    @endif

                                    <!-- Status Pill -->
                                    <div class="flex items-center space-x-1.5">
                                        @if($match->preview_status === \App\Models\GameMatch::PREVIEW_STATUS_DRAFT)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                                Draft
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                                Published
                                            </span>
                                        @endif

                                        @if($match->preview_generated_at)
                                            <span class="text-[10px] text-slate-500 font-mono">
                                                Gen: {{ $match->preview_generated_at->diffForHumans(null, true) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Content & SEO Health -->
                            <td class="py-4 px-4 sm:px-6">
                                <div class="space-y-1 text-xs">
                                    <div class="flex items-center space-x-2">
                                        <span class="font-mono text-slate-300 font-bold">{{ $match->previewWordCount() }} words</span>
                                        <span class="text-slate-600">•</span>
                                        <span class="text-slate-400 font-mono text-[11px]">~{{ $match->previewReadingMinutes() }} min read</span>
                                    </div>
                                    <div class="flex items-center space-x-1.5 flex-wrap gap-y-1">
                                        <!-- SEO Title Badge -->
                                        @if(filled($match->seo_title))
                                            <span class="px-1.5 py-0.5 rounded bg-emerald-500/10 text-emerald-400 text-[10px] font-semibold" title="Custom SEO Title Set">
                                                ✓ Title
                                            </span>
                                        @else
                                            <span class="px-1.5 py-0.5 rounded bg-slate-800 text-slate-400 text-[10px]" title="Default SEO Title">
                                                Default Title
                                            </span>
                                        @endif

                                        <!-- SEO Description Badge -->
                                        @if(filled($match->seo_description))
                                            <span class="px-1.5 py-0.5 rounded bg-emerald-500/10 text-emerald-400 text-[10px] font-semibold" title="Custom Meta Description Set">
                                                ✓ Meta Desc
                                            </span>
                                        @else
                                            <span class="px-1.5 py-0.5 rounded bg-slate-800 text-slate-400 text-[10px]">
                                                Auto Desc
                                            </span>
                                        @endif

                                        <!-- Keywords Badge -->
                                        @if(filled($match->seo_keywords))
                                            <span class="px-1.5 py-0.5 rounded bg-sky-500/10 text-sky-400 text-[10px] font-semibold" title="Focus Keywords Set">
                                                ✓ Keywords
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Actions -->
                            <td class="py-4 px-4 sm:px-6 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <!-- Edit Preview & SEO -->
                                    <a href="{{ route('admin.previews.edit', $match) }}"
                                       class="px-3 py-1.5 rounded-xl bg-sky-500/10 hover:bg-sky-500/20 text-sky-400 text-xs font-bold border border-sky-500/30 transition-all">
                                        Edit SEO & Content
                                    </a>

                                    <!-- Quick AI Generate -->
                                    <form action="{{ route('admin.previews.generate', $match) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" title="Generate / Regenerate with Gemini AI"
                                                class="px-2.5 py-1.5 rounded-xl bg-slate-800 hover:bg-indigo-600/30 text-indigo-400 hover:text-indigo-300 text-xs font-semibold border border-slate-700 transition-all">
                                            ⚡ AI Gen
                                        </button>
                                    </form>

                                    <!-- Toggle Publish -->
                                    <form action="{{ route('admin.previews.toggle', $match) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" title="Toggle Publish / Draft"
                                                class="px-2.5 py-1.5 rounded-xl {{ $match->preview_status === \App\Models\GameMatch::PREVIEW_STATUS_DRAFT ? 'bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 hover:bg-slate-700 text-slate-400 border border-slate-700' }} text-xs font-semibold transition-all">
                                            {{ $match->preview_status === \App\Models\GameMatch::PREVIEW_STATUS_DRAFT ? 'Publish' : 'Draft' }}
                                        </button>
                                    </form>

                                    <!-- Public View Link -->
                                    <a href="{{ $match->canonicalUrl() }}" target="_blank" title="View Public Match Page"
                                       class="p-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white transition-all border border-slate-700">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-500 text-sm">
                                <div class="max-w-md mx-auto space-y-2">
                                    <p class="font-bold text-slate-400">No match fixtures found matching your criteria.</p>
                                    <p class="text-xs">Create fixtures or run the ingestion pipeline to manage previews.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($matches->hasPages())
            <div class="p-4 border-t border-slate-800">
                {{ $matches->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
