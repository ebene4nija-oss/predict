@extends('layouts.app')

@section('title', 'Edit Fixture — Guaranteed Correct Admin')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Admin Navigation Bar -->
    @include('admin.partials.nav')

    <!-- Top Action Bar & Title -->
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.matches.index') }}" class="inline-flex items-center space-x-1.5 text-xs text-slate-400 hover:text-sky-400 transition-colors mb-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                <span>Back to Matches</span>
            </a>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white">Edit Fixture: {{ $match->home_team }} vs {{ $match->away_team }}</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">Modify fixture parameters, kickoff schedule, or edit the AI-generated preview narrative.</p>
        </div>

        <a href="{{ route('admin.previews.edit', $match) }}"
           class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-indigo-600 hover:from-sky-400 hover:to-indigo-500 text-white text-xs font-extrabold shadow-lg shadow-sky-500/25 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            <span>📝 Open Preview & SEO Editor</span>
        </a>
    </div>

    <!-- Match Preview Quick Status Banner -->
    <div class="p-4 rounded-2xl glass-panel border border-sky-500/30 flex items-center justify-between gap-4">
        <div class="flex items-center space-x-3">
            <div class="w-8 h-8 rounded-xl bg-sky-500/20 text-sky-400 flex items-center justify-center font-bold">
                SEO
            </div>
            <div>
                <div class="text-xs font-bold text-white">
                    Preview Status:
                    <span class="font-mono text-sky-400 uppercase">{{ $match->preview_source ?: 'None' }}</span>
                    @if($match->isCustomPreview())
                        <span class="ml-1 px-1.5 py-0.5 rounded bg-purple-500/20 text-purple-300 text-[10px]">LOCKED</span>
                    @endif
                    ({{ $match->previewWordCount() }} words)
                </div>
                <div class="text-[11px] text-slate-400">
                    {{ $match->seoTitle() }}
                </div>
            </div>
        </div>

        <a href="{{ route('admin.previews.edit', $match) }}" class="text-xs font-extrabold text-[#38BDF8] hover:underline whitespace-nowrap">
            Manage Full Preview & SEO &rarr;
        </a>
    </div>

    <!-- Form Container -->
    <div class="p-6 sm:p-8 rounded-3xl glass-panel border border-slate-800 shadow-xl">
        <form action="{{ route('admin.matches.update', $match) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Teams and League -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <label for="home_team" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                        Home Team <span class="text-sky-400">*</span>
                    </label>
                    <input type="text" name="home_team" id="home_team" value="{{ old('home_team', $match->home_team) }}" required
                        class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl px-4 py-2.5 text-sm focus:border-sky-400 focus:ring-1 focus:ring-sky-400 focus:outline-none transition-all" />
                    @error('home_team')
                        <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="away_team" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                        Away Team <span class="text-sky-400">*</span>
                    </label>
                    <input type="text" name="away_team" id="away_team" value="{{ old('away_team', $match->away_team) }}" required
                        class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl px-4 py-2.5 text-sm focus:border-sky-400 focus:ring-1 focus:ring-sky-400 focus:outline-none transition-all" />
                    @error('away_team')
                        <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="league" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                        League <span class="text-sky-400">*</span>
                    </label>
                    <input type="text" name="league" id="league" value="{{ old('league', $match->league) }}" required
                        class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl px-4 py-2.5 text-sm focus:border-sky-400 focus:ring-1 focus:ring-sky-400 focus:outline-none transition-all" />
                    @error('league')
                        <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Kickoff Date & Time -->
            <div>
                <label for="kickoff_at" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                    Kickoff Date & Time <span class="text-sky-400">*</span>
                </label>
                <input type="datetime-local" name="kickoff_at" id="kickoff_at" value="{{ old('kickoff_at', $match->kickoff_at ? $match->kickoff_at->format('Y-m-d\TH:i') : '') }}" required
                    class="w-full md:w-1/2 bg-slate-900 border border-slate-700 text-white rounded-xl px-4 py-2.5 text-sm focus:border-sky-400 focus:ring-1 focus:ring-sky-400 focus:outline-none transition-all" />
                @error('kickoff_at')
                    <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Notes & Head-to-head -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 border-t border-slate-800/80 pt-5">
                <div>
                    <label for="h2h_summary" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">H2H Summary</label>
                    <textarea name="h2h_summary" id="h2h_summary" rows="4"
                        class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl p-3 text-sm focus:border-sky-400 focus:ring-1 focus:ring-sky-400 focus:outline-none transition-all">{{ old('h2h_summary', $match->h2h_summary) }}</textarea>
                    @error('h2h_summary')
                        <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="injury_notes" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Injury & Team News Notes</label>
                    <textarea name="injury_notes" id="injury_notes" rows="4"
                        class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl p-3 text-sm focus:border-sky-400 focus:ring-1 focus:ring-sky-400 focus:outline-none transition-all">{{ old('injury_notes', $match->injury_notes) }}</textarea>
                    @error('injury_notes')
                        <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- AI-Generated Preview Narrative -->
            <div class="border-t border-slate-800/80 pt-5">
                <label for="preview_text" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                    AI Match Preview Narrative (Gemini Editorial)
                </label>
                <p class="text-xs text-slate-400 mb-3">You can manually refine or override the AI-generated tactical summary shown to users on the match page.</p>
                <textarea name="preview_text" id="preview_text" rows="6"
                    placeholder="AI preview text will appear here once generated..."
                    class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl p-4 text-sm focus:border-sky-400 focus:ring-1 focus:ring-sky-400 focus:outline-none transition-all font-sans leading-relaxed">{{ old('preview_text', $match->preview_text) }}</textarea>
                @error('preview_text')
                    <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit Button -->
            <div class="border-t border-slate-800/80 pt-6 flex items-center justify-end space-x-4">
                <a href="{{ route('admin.matches.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs transition-all">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-extrabold text-xs shadow-lg shadow-sky-500/25 transition-all">
                    Update Fixture Details
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
