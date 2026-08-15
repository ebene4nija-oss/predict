@extends('layouts.app')

@section('title', 'Settle Match Result — Guaranteed Correct Admin')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
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
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white">Settle Match Result</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">Enter official final score to verify AI predictions and update the public track record.</p>
        </div>
    </div>

    <!-- Match Information Card -->
    <div class="p-6 rounded-3xl glass-panel border border-slate-800 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-800">
            <div>
                <span class="text-xs font-semibold text-sky-400 uppercase tracking-wider">{{ $match->league }}</span>
                <h2 class="text-xl font-black text-white mt-0.5">{{ $match->home_team }} vs {{ $match->away_team }}</h2>
                <p class="text-xs text-slate-400 font-mono mt-1">
                    Kickoff: {{ $match->kickoff_at ? $match->kickoff_at->format('M d, Y @ H:i') : 'TBD' }}
                </p>
            </div>

            <div>
                @if($match->result)
                    <div class="px-4 py-2 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold text-center">
                        <span class="block text-[10px] uppercase text-emerald-500 font-semibold mb-0.5">Status</span>
                        Already Settled: {{ $match->result->home_score }} - {{ $match->result->away_score }}
                    </div>
                @else
                    <div class="px-4 py-2 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-bold text-center">
                        <span class="block text-[10px] uppercase text-amber-500 font-semibold mb-0.5">Status</span>
                        Pending Result
                    </div>
                @endif
            </div>
        </div>

        <!-- Score Input Form -->
        <form action="{{ route('admin.matches.settle.store', $match) }}" method="POST" class="space-y-6 pt-2">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Home Team Score -->
                <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 text-center space-y-2">
                    <label for="home_score" class="block text-sm font-bold text-slate-200">
                        {{ $match->home_team }} Score
                    </label>
                    <input type="number" name="home_score" id="home_score" min="0" required
                        value="{{ old('home_score', $match->result->home_score ?? 0) }}"
                        class="w-24 text-center bg-slate-900 border border-slate-700 text-white font-mono text-2xl font-black rounded-xl py-2 focus:border-sky-400 focus:ring-1 focus:ring-sky-400 focus:outline-none" />
                    @error('home_score')
                        <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Away Team Score -->
                <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 text-center space-y-2">
                    <label for="away_score" class="block text-sm font-bold text-slate-200">
                        {{ $match->away_team }} Score
                    </label>
                    <input type="number" name="away_score" id="away_score" min="0" required
                        value="{{ old('away_score', $match->result->away_score ?? 0) }}"
                        class="w-24 text-center bg-slate-900 border border-slate-700 text-white font-mono text-2xl font-black rounded-xl py-2 focus:border-sky-400 focus:ring-1 focus:ring-sky-400 focus:outline-none" />
                    @error('away_score')
                        <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Submit Button -->
            <div class="border-t border-slate-800 pt-6 flex items-center justify-end space-x-4">
                <a href="{{ route('admin.matches.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs transition-all">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white font-extrabold text-xs shadow-lg shadow-emerald-500/25 transition-all">
                    Confirm Result & Update Track Record
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
