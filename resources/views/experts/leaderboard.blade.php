@extends('layouts.app')

@section('title', 'Official Expert Analyst Leaderboard — Prophet AI')

@section('content')
    <div class="max-w-5xl mx-auto py-6 space-y-6">
        <x-telegram-banner />

        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/30 text-xs font-bold mb-2">
                    <span>🏆 PUBLIC PERFORMANCE LEADERBOARD</span>
                </div>
                <h1 class="text-3xl font-extrabold text-white">Expert Analysts Track Record</h1>
                <p class="text-sm text-slate-400">Verified hit rates and accuracy metrics for all human expert handicappers.</p>
            </div>

            <a href="{{ route('expert.picks') }}" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs border border-slate-700 transition-all">
                &larr; Back to Expert Picks Feed
            </a>
        </div>

        <div class="p-6 rounded-3xl glass-panel border border-slate-800 space-y-4">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="text-xs uppercase bg-slate-900/80 text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="py-3 px-4">Rank</th>
                            <th class="py-3 px-4">Expert Strategist</th>
                            <th class="py-3 px-4 text-center">Total Picks</th>
                            <th class="py-3 px-4 text-center">Settled Wins</th>
                            <th class="py-3 px-4 text-right">Hit Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($experts as $index => $expert)
                            <tr class="hover:bg-slate-900/40 transition-colors">
                                <td class="py-4 px-4 font-black font-mono text-base {{ $loop->first ? 'text-amber-400' : ($loop->iteration == 2 ? 'text-slate-300' : ($loop->iteration == 3 ? 'text-amber-600' : 'text-slate-500')) }}">
                                    #{{ $loop->iteration }}
                                </td>
                                <td class="py-4 px-4">
                                    <div class="flex items-center space-x-3">
                                        <img src="{{ $expert->photo_path ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100' }}" class="w-10 h-10 rounded-full border border-amber-500/30 object-cover" alt="{{ $expert->name }}">
                                        <div>
                                            <div class="font-bold text-white flex items-center space-x-1.5">
                                                <span>{{ $expert->name }}</span>
                                                @if($loop->first)
                                                    <span class="text-xs text-amber-400">👑 Top Analyst</span>
                                                @endif
                                            </div>
                                            <div class="text-xs text-slate-400 truncate max-w-xs">{{ $expert->bio }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-center font-bold text-slate-200 font-mono">{{ $expert->total_picks }}</td>
                                <td class="py-4 px-4 text-center font-bold text-emerald-400 font-mono">{{ $expert->won_count }} / {{ $expert->settled_count }}</td>
                                <td class="py-4 px-4 text-right font-black text-amber-400 font-mono text-base">
                                    {{ $expert->win_rate }}%
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-500 text-sm">No expert profiles found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
