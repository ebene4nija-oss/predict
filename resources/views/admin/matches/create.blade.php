@extends('layouts.app')

@section('title', 'Create New Match Fixture — Prophet AI Admin')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Admin Navigation Bar -->
    <div class="flex items-center space-x-1 p-1 rounded-2xl glass-panel mb-6 overflow-x-auto">
        <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Dashboard</a>
        <a href="{{ route('admin.matches.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.matches.*') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Matches</a>
        <a href="{{ route('admin.predictions.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.predictions.*') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Predictions</a>
        <a href="{{ route('admin.users.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.users.*') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Users</a>
        <a href="{{ route('admin.experts.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.experts.*') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Experts</a>
        <a href="{{ route('admin.ads.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.ads.*') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Ads</a>
        <a href="{{ route('admin.settings') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.settings*') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Settings</a>
    </div>

    <!-- Top Action Bar & Title -->
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.matches.index') }}" class="inline-flex items-center space-x-1.5 text-xs text-slate-400 hover:text-sky-400 transition-colors mb-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                <span>Back to Matches</span>
            </a>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white">Create New Match Fixture</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">Ingest a new fixture. Claude and Gemini AI engines will calculate predictions automatically upon saving.</p>
        </div>
    </div>

    <!-- Form Container -->
    <div class="p-6 sm:p-8 rounded-3xl glass-panel border border-slate-800 shadow-xl">
        <form action="{{ route('admin.matches.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Teams and League -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <label for="home_team" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                        Home Team <span class="text-sky-400">*</span>
                    </label>
                    <input type="text" name="home_team" id="home_team" value="{{ old('home_team') }}" required
                        placeholder="e.g. Arsenal"
                        class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl px-4 py-2.5 text-sm focus:border-sky-400 focus:ring-1 focus:ring-sky-400 focus:outline-none transition-all" />
                    @error('home_team')
                        <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="away_team" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                        Away Team <span class="text-sky-400">*</span>
                    </label>
                    <input type="text" name="away_team" id="away_team" value="{{ old('away_team') }}" required
                        placeholder="e.g. Chelsea"
                        class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl px-4 py-2.5 text-sm focus:border-sky-400 focus:ring-1 focus:ring-sky-400 focus:outline-none transition-all" />
                    @error('away_team')
                        <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="league" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                        League <span class="text-sky-400">*</span>
                    </label>
                    <input type="text" name="league" id="league" value="{{ old('league') }}" required
                        placeholder="e.g. Premier League"
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
                <input type="datetime-local" name="kickoff_at" id="kickoff_at" value="{{ old('kickoff_at') }}" required
                    class="w-full md:w-1/2 bg-slate-900 border border-slate-700 text-white rounded-xl px-4 py-2.5 text-sm focus:border-sky-400 focus:ring-1 focus:ring-sky-400 focus:outline-none transition-all" />
                @error('kickoff_at')
                    <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Form parameters for xG calculation -->
            <div class="border-t border-slate-800/80 pt-5">
                <h3 class="text-sm font-bold text-white mb-1">Team Expected Goals (xG) Baseline Parameters</h3>
                <p class="text-xs text-slate-400 mb-4">Used by the Poisson statistical model to calculate probabilities and expected scores.</p>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <label for="home_gf" class="block text-xs font-semibold text-slate-300 mb-1.5">Home Goals For (GF)</label>
                        <input type="number" step="0.1" name="home_gf" id="home_gf" value="{{ old('home_gf', '1.8') }}"
                            class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl px-3 py-2 text-sm focus:border-sky-400 focus:ring-1 focus:ring-sky-400 focus:outline-none transition-all" />
                        @error('home_gf')
                            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="home_ga" class="block text-xs font-semibold text-slate-300 mb-1.5">Home Goals Against (GA)</label>
                        <input type="number" step="0.1" name="home_ga" id="home_ga" value="{{ old('home_ga', '1.0') }}"
                            class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl px-3 py-2 text-sm focus:border-sky-400 focus:ring-1 focus:ring-sky-400 focus:outline-none transition-all" />
                        @error('home_ga')
                            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="away_gf" class="block text-xs font-semibold text-slate-300 mb-1.5">Away Goals For (GF)</label>
                        <input type="number" step="0.1" name="away_gf" id="away_gf" value="{{ old('away_gf', '1.2') }}"
                            class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl px-3 py-2 text-sm focus:border-sky-400 focus:ring-1 focus:ring-sky-400 focus:outline-none transition-all" />
                        @error('away_gf')
                            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="away_ga" class="block text-xs font-semibold text-slate-300 mb-1.5">Away Goals Against (GA)</label>
                        <input type="number" step="0.1" name="away_ga" id="away_ga" value="{{ old('away_ga', '1.5') }}"
                            class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl px-3 py-2 text-sm focus:border-sky-400 focus:ring-1 focus:ring-sky-400 focus:outline-none transition-all" />
                        @error('away_ga')
                            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Notes & Head-to-head -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 border-t border-slate-800/80 pt-5">
                <div>
                    <label for="h2h_summary" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">H2H Summary</label>
                    <textarea name="h2h_summary" id="h2h_summary" rows="3"
                        placeholder="Recent head-to-head history details..."
                        class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl p-3 text-sm focus:border-sky-400 focus:ring-1 focus:ring-sky-400 focus:outline-none transition-all">{{ old('h2h_summary') }}</textarea>
                    @error('h2h_summary')
                        <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="injury_notes" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Injury & Team News Notes</label>
                    <textarea name="injury_notes" id="injury_notes" rows="3"
                        placeholder="Key missing players or injury updates..."
                        class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl p-3 text-sm focus:border-sky-400 focus:ring-1 focus:ring-sky-400 focus:outline-none transition-all">{{ old('injury_notes') }}</textarea>
                    @error('injury_notes')
                        <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Submit Button -->
            <div class="border-t border-slate-800/80 pt-6 flex items-center justify-end space-x-4">
                <a href="{{ route('admin.matches.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs transition-all">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-extrabold text-xs shadow-lg shadow-sky-500/25 transition-all">
                    Create Fixture & Trigger AI Pipeline
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
