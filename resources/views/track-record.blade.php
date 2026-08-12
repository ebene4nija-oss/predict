@extends('layouts.app')

@section('title', 'Public Track Record & Accuracy Audit — Prophet AI')

@section('content')
    <x-ad-banner type="header" />

    <div class="space-y-8">
        <!-- Header -->
        <div>
            <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 text-xs font-bold mb-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>AUTOMATICALLY AUDITED DATA</span>
            </div>
            <h1 class="text-3xl font-extrabold text-white">Public Track Record</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">100% transparent historical accuracy computed directly from settled match results.</p>
        </div>

        <!-- Top Accuracy Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-6 rounded-2xl glass-panel border border-sky-500/30">
                <div class="text-xs text-slate-400 font-semibold uppercase">Overall AI Win Rate</div>
                <div class="text-3xl font-black text-[#38BDF8] mt-2 font-mono">{{ $stats['ai']['overall']['rate'] }}%</div>
                <div class="text-[11px] text-slate-500 mt-1">{{ $stats['ai']['overall']['won'] }} won / {{ $stats['ai']['overall']['total'] }} settled</div>
            </div>

            <div class="p-6 rounded-2xl glass-panel border border-emerald-500/30">
                <div class="text-xs text-slate-400 font-semibold uppercase">Over 2.5 Market Rate</div>
                <div class="text-3xl font-black text-emerald-400 mt-2 font-mono">{{ $stats['ai']['over_2_5']['rate'] }}%</div>
                <div class="text-[11px] text-slate-500 mt-1">{{ $stats['ai']['over_2_5']['won'] }} won / {{ $stats['ai']['over_2_5']['total'] }} settled</div>
            </div>

            <div class="p-6 rounded-2xl glass-panel border border-indigo-500/30">
                <div class="text-xs text-slate-400 font-semibold uppercase">Both Teams Score (GG)</div>
                <div class="text-3xl font-black text-indigo-400 mt-2 font-mono">{{ $stats['ai']['gg']['rate'] }}%</div>
                <div class="text-[11px] text-slate-500 mt-1">{{ $stats['ai']['gg']['won'] }} won / {{ $stats['ai']['gg']['total'] }} settled</div>
            </div>

            <div class="p-6 rounded-2xl glass-panel border border-amber-500/30">
                <div class="text-xs text-slate-400 font-semibold uppercase">Human Expert Rate</div>
                <div class="text-3xl font-black text-[#F5A623] mt-2 font-mono">{{ $stats['expert']['overall']['rate'] }}%</div>
                <div class="text-[11px] text-slate-500 mt-1">{{ $stats['expert']['overall']['won'] }} won / {{ $stats['expert']['overall']['total'] }} settled</div>
            </div>
        </div>

        <!-- Historical Settled Results Log -->
        <div class="space-y-4">
            <h2 class="text-xl font-bold text-white">Settled Match Results Audit Log</h2>

            <div class="space-y-3">
                @foreach($history as $result)
                    <div class="p-5 rounded-2xl glass-panel border border-slate-800 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center space-x-2 text-xs text-slate-400 mb-1">
                                <span>{{ $result->match->league }}</span>
                                <span>•</span>
                                <span>Settled: {{ $result->settled_at ? $result->settled_at->format('M d, Y') : 'Recently' }}</span>
                            </div>
                            <div class="font-bold text-base text-white">
                                {{ $result->match->home_team }} <span class="text-sky-400 font-mono px-1 font-black">{{ $result->home_score }} - {{ $result->away_score }}</span> {{ $result->match->away_team }}
                            </div>
                        </div>

                        <!-- Settled Pick Badges -->
                        <div class="flex flex-wrap items-center gap-3">
                            @foreach($result->match->predictions as $pred)
                                @php
                                    $h = $result->home_score;
                                    $a = $result->away_score;
                                    $actual = match($pred->market) {
                                        'win_draw_loss' => $h > $a ? 'Home Win' : ($h === $a ? 'Draw' : 'Away Win'),
                                        'gg' => ($h > 0 && $a > 0) ? 'GG (Yes)' : 'NG (No)',
                                        'over_2_5' => ($h + $a) > 2.5 ? 'Over 2.5' : 'Under 2.5',
                                        default => '',
                                    };
                                    $won = $pred->pick === $actual;
                                @endphp

                                <div class="px-3 py-1.5 rounded-xl text-xs font-semibold flex items-center space-x-2 border {{ $won ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : 'bg-rose-500/10 text-rose-400 border-rose-500/30' }}">
                                    <span class="w-2 h-2 rounded-full {{ $won ? 'bg-emerald-400' : 'bg-rose-400' }}"></span>
                                    <span>AI {{ uppercase_mkt($pred->market) }}: {{ $pred->pick }}</span>
                                    <span class="font-bold font-mono">({{ $won ? 'WON' : 'LOST' }})</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
