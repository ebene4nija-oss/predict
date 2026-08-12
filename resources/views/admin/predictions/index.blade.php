@extends('layouts.app')

@section('title', 'AI Prediction Override Manager — Admin Portal')

@section('content')
    <div class="max-w-6xl mx-auto space-y-6 py-4">
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

        <!-- Flash Message Banner -->
        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-semibold flex items-center space-x-2 mb-6">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Page Header & Market Selector -->
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white">AI Prediction Override Manager</h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-1">Review, refine, and override automated AI market probability outputs and picks.</p>
            </div>

            <!-- Market Filter Tabs -->
            <div class="p-1 rounded-2xl glass-panel border border-slate-800 flex items-center space-x-1">
                <a href="{{ route('admin.predictions.index', ['market' => 'win_draw_loss']) }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ $market === 'win_draw_loss' ? 'bg-sky-500 text-slate-950 font-black shadow-md shadow-sky-500/20' : 'text-slate-400 hover:text-white' }}">
                    1X2 Match Winner
                </a>
                <a href="{{ route('admin.predictions.index', ['market' => 'gg']) }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ $market === 'gg' ? 'bg-sky-500 text-slate-950 font-black shadow-md shadow-sky-500/20' : 'text-slate-400 hover:text-white' }}">
                    Both Teams to Score (GG)
                </a>
                <a href="{{ route('admin.predictions.index', ['market' => 'over_2_5']) }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ $market === 'over_2_5' ? 'bg-sky-500 text-slate-950 font-black shadow-md shadow-sky-500/20' : 'text-slate-400 hover:text-white' }}">
                    Over 2.5 Goals
                </a>
            </div>
        </div>

        <!-- Predictions List -->
        <div class="space-y-4">
            @forelse($predictions as $prediction)
                <div class="p-5 rounded-2xl glass-panel border border-slate-800 hover:border-slate-700 transition-all space-y-4">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 pb-3 border-b border-slate-800/80">
                        <div>
                            <span class="text-[10px] uppercase font-bold tracking-wider text-slate-500">{{ $prediction->match->league ?? 'League Fixture' }}</span>
                            <h3 class="text-base font-extrabold text-white">
                                {{ $prediction->match->home_team ?? 'Home Team' }} <span class="text-slate-500 font-normal">vs</span> {{ $prediction->match->away_team ?? 'Away Team' }}
                            </h3>
                        </div>

                        <div class="flex items-center space-x-2">
                            <span class="px-2.5 py-1 rounded-lg text-[11px] font-mono font-bold bg-slate-900 text-sky-400 border border-slate-800">
                                Current: <span class="text-white">{{ $prediction->pick }}</span> ({{ round($prediction->probability * 100, 1) }}%)
                            </span>
                            @if($prediction->is_top10)
                                <span class="px-2 py-0.5 rounded-md bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 text-[10px] font-bold">TOP 10</span>
                            @endif
                            @if($prediction->is_ai5)
                                <span class="px-2 py-0.5 rounded-md bg-indigo-500/10 text-indigo-400 border border-indigo-500/30 text-[10px] font-bold">AI TOP 5</span>
                            @endif
                        </div>
                    </div>

                    <!-- Edit Form -->
                    <form action="{{ route('admin.predictions.update', $prediction) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                        @csrf
                        @method('PUT')

                        <div class="sm:col-span-3">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Pick</label>
                            <input type="text" name="pick" value="{{ old('pick', $prediction->pick) }}" required class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs font-semibold focus:border-sky-400 focus:outline-none">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Probability (0.01-0.99)</label>
                            <input type="number" step="0.0001" min="0.01" max="0.99" name="probability" value="{{ old('probability', $prediction->probability) }}" required class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs font-mono focus:border-sky-400 focus:outline-none">
                        </div>

                        <div class="sm:col-span-4">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Rationale</label>
                            <input type="text" name="rationale" value="{{ old('rationale', $prediction->rationale) }}" placeholder="Short analysis rationale..." class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs focus:border-sky-400 focus:outline-none">
                        </div>

                        <div class="sm:col-span-2 flex items-center space-x-3 py-2">
                            <label class="inline-flex items-center space-x-1.5 cursor-pointer">
                                <input type="checkbox" name="is_top10" value="1" {{ $prediction->is_top10 ? 'checked' : '' }} class="rounded bg-slate-900 border-slate-700 text-sky-500 focus:ring-sky-400">
                                <span class="text-xs text-slate-300 font-semibold">Top 10</span>
                            </label>

                            <label class="inline-flex items-center space-x-1.5 cursor-pointer">
                                <input type="checkbox" name="is_ai5" value="1" {{ $prediction->is_ai5 ? 'checked' : '' }} class="rounded bg-slate-900 border-slate-700 text-indigo-500 focus:ring-indigo-400">
                                <span class="text-xs text-slate-300 font-semibold">AI 5</span>
                            </label>
                        </div>

                        <div class="sm:col-span-1 flex justify-end">
                            <button type="submit" class="w-full py-2 px-3 rounded-xl bg-gradient-to-r from-sky-500 to-indigo-600 hover:from-sky-400 hover:to-indigo-500 text-white text-xs font-bold transition-all shadow-md shadow-sky-500/20">
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            @empty
                <div class="p-8 rounded-2xl glass-panel border border-slate-800 text-center text-slate-400">
                    No predictions found for this market. Run the AI pipeline or ingest fixtures to populate.
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="pt-4">
            {{ $predictions->appends(['market' => $market])->links() }}
        </div>
    </div>
@endsection
