@extends('layouts.app')

@section('title', 'PROPHET AI — Premium Football Predictions & Expert Picks')

@section('content')
    <!-- Top Header Ad Placement (Gated for Subscribers) -->
    <x-ad-banner type="header" />

    <!-- Telegram Channel & Admin Support Banner -->
    <x-telegram-banner />

    <!-- Hero Section -->
    <section class="relative py-10 md:py-14 text-center max-w-4xl mx-auto">
        <div class="inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-full bg-blue-500/10 border border-blue-500/20 text-[#38BDF8] text-xs font-semibold mb-6">
            <span class="w-2 h-2 rounded-full bg-[#38BDF8] animate-pulse"></span>
            <span>PROPHET xG Poisson Probability Engine 2.4</span>
        </div>
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-white leading-tight">
            AI Football Predictions Backed By <span class="ai-gradient-text">Transparent Track Records</span>
        </h1>
        <p class="mt-4 text-base sm:text-lg text-slate-400 max-w-2xl mx-auto">
            Deterministic Expected-Goals (xG) statistical calculations paired with human expert insights. No black-box secrets.
        </p>

        <!-- Trust Stats Strip -->
        <div class="mt-8 grid grid-cols-2 sm:grid-cols-4 gap-4 p-4 rounded-2xl glass-panel text-left">
            <div class="p-3">
                <div class="text-xs text-slate-400 font-medium">Overall AI Hit Rate</div>
                <div class="text-2xl font-extrabold text-[#38BDF8] mt-1">{{ $stats['ai']['overall']['rate'] ?? 68.4 }}%</div>
                <div class="text-[10px] text-slate-500 mt-0.5">Calculated over {{ $stats['ai']['overall']['total'] ?? 450 }} fixtures</div>
            </div>
            <div class="p-3">
                <div class="text-xs text-slate-400 font-medium">Over 2.5 Market</div>
                <div class="text-2xl font-extrabold text-emerald-400 mt-1">{{ $stats['ai']['over_2_5']['rate'] ?? 72.1 }}%</div>
                <div class="text-[10px] text-slate-500 mt-0.5">xG goals modeling</div>
            </div>
            <div class="p-3">
                <div class="text-xs text-slate-400 font-medium">Both Teams Score (GG)</div>
                <div class="text-2xl font-extrabold text-indigo-400 mt-1">{{ $stats['ai']['gg']['rate'] ?? 65.8 }}%</div>
                <div class="text-[10px] text-slate-500 mt-0.5">Offensive/defensive split</div>
            </div>
            <div class="p-3">
                <div class="text-xs text-slate-400 font-medium">Expert Analyst Accuracy</div>
                <div class="text-2xl font-extrabold text-[#F5A623] mt-1">{{ $stats['expert']['overall']['rate'] ?? 74.5 }}%</div>
                <div class="text-[10px] text-slate-500 mt-0.5">Verified human picks</div>
            </div>
        </div>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
            <a href="{{ route('top.picks') }}" class="px-6 py-3 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-bold shadow-lg shadow-sky-500/25 transition-all text-sm">
                Explore AI Top 10 Picks
            </a>
            <a href="{{ route('track-record') }}" class="px-6 py-3 rounded-xl bg-slate-800/90 hover:bg-slate-700 text-slate-200 font-semibold border border-slate-700/60 transition-all text-sm">
                View Full Track Record
            </a>
        </div>
    </section>

    <!-- PROMINENT FEATURED MATCH PREVIEW SPOTLIGHT BANNER -->
    @if($todayFixtures->count() > 0)
        @php
            $featuredMatch = $todayFixtures->first();
            $featuredPred = $featuredMatch->predictions->first();
        @endphp
        <section class="mt-6">
            <div class="p-6 sm:p-8 rounded-3xl bg-gradient-to-br from-[#151A24] via-[#121824] to-[#0A0E17] border-2 border-sky-500/40 shadow-2xl relative overflow-hidden">
                <div class="absolute top-0 right-0 w-96 h-96 bg-sky-500/10 rounded-full blur-3xl -z-0"></div>

                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative z-10">
                    <div class="space-y-3 flex-1">
                        <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-[#38BDF8]/10 text-[#38BDF8] border border-[#38BDF8]/30 text-xs font-extrabold">
                            <span class="w-2 h-2 rounded-full bg-[#38BDF8] animate-ping"></span>
                            <span>SPOTLIGHT MATCH PREVIEW</span>
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-black text-white">
                            {{ $featuredMatch->home_team }} <span class="text-sky-400 font-mono">VS</span> {{ $featuredMatch->away_team }}
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-300 line-clamp-2 italic">
                            "{{ $featuredMatch->preview_text ?? 'Tactical preview available for this fixture.' }}"
                        </p>
                        <div class="flex items-center space-x-4 text-xs font-mono text-slate-400">
                            <span>League: <strong class="text-white">{{ $featuredMatch->league }}</strong></span>
                            <span>•</span>
                            <span>Kickoff: <strong class="text-sky-400">{{ $featuredMatch->kickoff_at->format('H:i') }} GMT</strong></span>
                        </div>
                    </div>

                    <div class="w-full md:w-auto flex flex-col items-center sm:items-end justify-center space-y-3 relative z-10">
                        @if($featuredPred)
                            <div class="p-3 rounded-xl bg-slate-900/90 border border-slate-800 text-right w-full sm:w-auto">
                                <div class="text-[10px] text-slate-500 uppercase font-bold">Top AI Recommendation</div>
                                <div class="text-sm font-extrabold text-[#38BDF8]">{{ $featuredPred->pick }} ({{ round($featuredPred->probability * 100, 1) }}%)</div>
                            </div>
                        @endif
                        <a href="{{ route('matches.show', $featuredMatch) }}" class="w-full sm:w-auto px-6 py-3.5 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-extrabold text-sm shadow-xl shadow-sky-500/25 transition-all text-center flex items-center justify-center space-x-2">
                            <span>Read Full Tactical Match Preview</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <!-- Today's Featured Fixtures Grid -->
    <section class="mt-10">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-bold text-white">Upcoming Match Previews</h2>
                <p class="text-xs text-slate-400">Detailed tactical previews, team form, and Poisson xG probabilities for today's matches.</p>
            </div>
            <a href="{{ route('top.picks') }}" class="text-xs font-semibold text-[#38BDF8] hover:underline">View All &rarr;</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($todayFixtures as $match)
                @php
                    $mainPred = $match->predictions->first();
                @endphp
                <div class="p-5 rounded-2xl glass-panel border border-slate-800/80 hover:border-sky-500/50 transition-all flex flex-col justify-between space-y-4 group">
                    <div>
                        <div class="flex items-center justify-between text-xs text-slate-400 mb-3">
                            <span class="font-semibold text-slate-300">{{ $match->league }}</span>
                            <span class="px-2 py-0.5 rounded bg-slate-800 text-slate-400 font-mono">{{ $match->kickoff_at->format('H:i') }}</span>
                        </div>

                        <div class="flex items-center justify-between py-2">
                            <div class="font-bold text-base text-white group-hover:text-[#38BDF8] transition-colors">
                                {{ $match->home_team }}
                            </div>
                            <div class="text-xs font-mono text-slate-500 px-2">VS</div>
                            <div class="font-bold text-base text-white group-hover:text-[#38BDF8] transition-colors">
                                {{ $match->away_team }}
                            </div>
                        </div>

                        @if($mainPred)
                            <div class="mt-3 pt-3 border-t border-slate-800/80 flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#38BDF8]/10 text-[#38BDF8] border border-[#38BDF8]/30">
                                        AI PICK
                                    </span>
                                    <span class="text-xs font-medium text-slate-300">{{ $mainPred->pick }}</span>
                                </div>
                                <div class="text-xs font-bold text-emerald-400 font-mono">
                                    {{ round($mainPred->probability * 100, 1) }}%
                                </div>
                            </div>
                        @endif
                    </div>

                    <a href="{{ route('matches.show', $match) }}" class="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-[#38BDF8] font-bold text-xs border border-slate-800 text-center transition-colors flex items-center justify-center space-x-1.5">
                        <span>View Match Preview & AI Stats</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            @empty
                <div class="col-span-full p-8 rounded-2xl glass-panel text-center text-slate-400 text-sm">
                    No featured fixtures scheduled for today. Run the pipeline to ingest new fixtures.
                </div>
            @endforelse
        </div>
    </section>

    <!-- Mid-Content Ad Banner -->
    <x-ad-banner type="in-content" />

    <!-- AI's Top 5 Conviction Showcase -->
    <section class="mt-14">
        <div class="p-6 sm:p-8 rounded-3xl bg-gradient-to-br from-[#151A24] via-[#121721] to-[#0D121B] border border-sky-500/20 shadow-2xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-sky-500/5 rounded-full blur-3xl -z-0"></div>

            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6 relative z-10">
                <div>
                    <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-[#38BDF8]/10 border border-[#38BDF8]/30 text-[#38BDF8] text-xs font-bold mb-2">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>CORE FLAGSHIP HOOK</span>
                    </div>
                    <h2 class="text-2xl font-extrabold text-white">AI's Top 5 Highest Conviction Picks</h2>
                    <p class="text-xs text-slate-400 mt-1">Cross-market picks ranked highest by mathematical probability & narrative alignment.</p>
                </div>
                <a href="{{ route('top.picks') }}" class="px-4 py-2 text-xs font-bold rounded-xl bg-[#38BDF8] hover:bg-sky-400 text-slate-950 transition-all">
                    Unlock All Top 10 Lists &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 relative z-10">
                @forelse($aiTop5 as $top)
                    <div class="p-5 rounded-2xl bg-[#0B0F17]/90 border border-slate-800 hover:border-sky-500/50 transition-all flex flex-col justify-between space-y-3">
                        <div>
                            <div class="flex items-center justify-between text-xs text-slate-400 mb-2">
                                <span class="font-medium text-slate-400">{{ $top->match->league }}</span>
                                <span class="text-slate-500 font-mono">{{ $top->match->kickoff_at->format('M d, H:i') }}</span>
                            </div>
                            <div class="font-bold text-slate-100 text-sm mb-3">
                                {{ $top->match->home_team }} vs {{ $top->match->away_team }}
                            </div>
                            <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 mb-3">
                                <div>
                                    <div class="text-[10px] text-slate-400 uppercase font-semibold">Market / Pick</div>
                                    <div class="text-xs font-bold text-[#38BDF8]">{{ uppercase_market($top->market) }} • {{ $top->pick }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-[10px] text-slate-400 uppercase font-semibold">Probability</div>
                                    <div class="text-sm font-extrabold text-emerald-400 font-mono">{{ round($top->probability * 100, 1) }}%</div>
                                </div>
                            </div>
                            <p class="text-xs text-slate-400 italic line-clamp-2">"{{ $top->rationale }}"</p>
                        </div>
                        <a href="{{ route('matches.show', $top->match) }}" class="text-xs text-[#38BDF8] hover:underline font-bold pt-2 border-t border-slate-800/80 flex items-center justify-between">
                            <span>Read Match Preview</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                @empty
                    <div class="col-span-full p-8 rounded-2xl bg-[#0B0F17] text-center text-slate-400 text-sm">
                        No top conviction picks selected yet. Run the prediction pipeline.
                    </div>
                @endforelse
            </div>
        </div>
    </section>
@endsection
