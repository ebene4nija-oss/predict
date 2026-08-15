@extends('layouts.app')

@section('title', 'Submit Expert Pick — Guaranteed Correct Portal')

@section('content')
    <div class="max-w-2xl mx-auto py-6">
        <div class="p-6 sm:p-8 rounded-3xl glass-panel border border-amber-500/30 space-y-6">
            <div class="flex items-center space-x-3">
                <span class="p-2.5 rounded-2xl bg-amber-500/10 text-amber-400 border border-amber-500/30">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </span>
                <div>
                    <h1 class="text-2xl font-extrabold text-white">Expert Submission Portal</h1>
                    <p class="text-xs text-slate-400">Publish your independent tactical pick for upcoming fixtures.</p>
                </div>
            </div>

            <form action="{{ route('expert.submit') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Select Fixture</label>
                    <select name="match_id" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:border-amber-400 focus:outline-none" required>
                        <option value="">-- Choose Upcoming Match --</option>
                        @foreach($upcomingMatches as $match)
                            <option value="{{ $match->id }}">{{ $match->home_team }} vs {{ $match->away_team }} ({{ $match->league }} - {{ $match->kickoff_at->format('M d, H:i') }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Target Market</label>
                        <select name="market" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:border-amber-400 focus:outline-none" required>
                            <option value="win_draw_loss">Match Winner (W/D/L)</option>
                            <option value="gg">Both Teams Score (GG)</option>
                            <option value="over_2_5">Over 2.5 Goals</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Your Recommended Pick</label>
                        <input type="text" name="pick" placeholder="e.g. Home Win, Over 2.5, GG (Yes)" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:border-amber-400 focus:outline-none" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Confidence Level (0.50 - 0.99)</label>
                        <input type="number" step="0.01" min="0.50" max="0.99" name="confidence" value="0.80" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-amber-400 focus:outline-none" required>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Tactical Rationale & Analysis</label>
                    <textarea name="rationale" rows="4" placeholder="Explain the key tactical factors, form split, or squad news behind this pick..." class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:border-amber-400 focus:outline-none" required></textarea>
                </div>

                <button type="submit" class="w-full py-3.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-extrabold text-sm transition-all shadow-lg shadow-amber-500/20">
                    Publish Expert Pick &rarr;
                </button>
            </form>
        </div>
    </div>
@endsection
