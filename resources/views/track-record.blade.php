@extends('layouts.app')

@section('title', 'Public Track Record & Accuracy Audit — Guaranteed Correct')

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

        <!-- Profitability & Calibration -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @php $roi = $stats['ai']['overall']['roi']; @endphp
            <div class="p-6 rounded-2xl glass-panel border border-slate-800 space-y-2">
                <div class="text-xs text-slate-400 font-semibold uppercase">Return on Investment</div>
                @if($roi === null)
                    <div class="text-2xl font-black text-slate-500 mt-1 font-mono">Not yet measurable</div>
                    <p class="text-[11px] text-slate-500">
                        ROI needs the price each pick was available at. Once odds are recorded, this
                        shows profit or loss per unit staked.
                    </p>
                @else
                    <div class="text-3xl font-black mt-1 font-mono {{ $roi >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                        {{ $roi > 0 ? '+' : '' }}{{ $roi }}%
                    </div>
                    <div class="text-[11px] text-slate-500">
                        {{ $stats['ai']['overall']['profit'] > 0 ? '+' : '' }}{{ $stats['ai']['overall']['profit'] }} units
                        across {{ $stats['ai']['overall']['staked'] }} priced picks
                    </div>
                    <p class="text-[11px] text-slate-500">
                        Profit per unit staked at the recorded odds. A high win rate at short prices
                        can still lose money, so this is the figure that matters.
                    </p>
                @endif
            </div>

            <div class="p-6 rounded-2xl glass-panel border border-slate-800 space-y-3">
                <div class="flex items-baseline justify-between">
                    <div class="text-xs text-slate-400 font-semibold uppercase">Calibration</div>
                    @if($stats['calibration']['brier'] !== null)
                        <div class="text-xs font-mono text-slate-300">
                            Brier <span class="text-white font-bold">{{ $stats['calibration']['brier'] }}</span>
                            <span class="text-slate-500">(n={{ $stats['calibration']['sample'] }})</span>
                        </div>
                    @endif
                </div>

                @if($stats['calibration']['sample'] === 0)
                    <p class="text-[11px] text-slate-500">No settled picks yet.</p>
                @else
                    <p class="text-[11px] text-slate-500">
                        Do our stated probabilities hold up? Each band compares what we claimed
                        against what actually happened. Closer is better; lower Brier is better.
                    </p>
                    <div class="space-y-1.5">
                        @foreach($stats['calibration']['buckets'] as $bucket)
                            @continue($bucket['total'] === 0)
                            <div class="flex items-center justify-between text-[11px] font-mono">
                                <span class="text-slate-400 w-20">{{ $bucket['label'] }}</span>
                                <div class="flex-1 mx-3 h-2 rounded-full bg-slate-800 overflow-hidden">
                                    <div class="h-full bg-sky-400/70" style="width: {{ $bucket['actual'] }}%"></div>
                                </div>
                                <span class="text-slate-300 w-28 text-right">
                                    {{ $bucket['actual'] }}% actual
                                    <span class="text-slate-500">/ {{ $bucket['total'] }}</span>
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <p class="text-[11px] text-slate-500">
            Only picks published before kickoff are counted. Predictions are locked at publication
            and cannot be edited once a fixture has started.
        </p>

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
                                    // Same grader the published accuracy figures use,
                                    // so a badge here can never contradict the headline stats.
                                    $won = \App\Support\MarketOutcome::isWinningPick(
                                        $pred->market,
                                        $pred->pick,
                                        $result->home_score,
                                        $result->away_score,
                                    );
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

            @if($history->hasPages())
                <div class="pt-2">
                    {{ $history->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
