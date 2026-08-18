@extends('layouts.app')

@section('title', 'Guaranteed Correct Score Tips — 100% Verified AI & Expert Football Predictions Today')
@section('meta_description', 'Looking for guaranteed correct score tips today? Get high-accuracy exact score predictions, Poisson mathematical matrix models, and verified expert football tips at GuaranteedCorrectScoreTips.com.')
@section('meta_keywords', 'guaranteed correct score tips, correct score tips, guaranteed correct score, exact score predictions, correct score prediction today, free correct score tips, sure correct score, VIP correct score tips, fixed correct score tips, football predictions today, high odds correct score multiples, soccer betting tips')
@section('canonical', route('home'))

@section('schema_json_ld')
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => [
            [
                '@type' => 'Question',
                'name' => 'What are guaranteed correct score tips?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'Guaranteed correct score tips are high-probability exact score predictions generated through expected-goals (xG) bivariate Poisson distributions, historical team metrics, and human tactical handicapper verification at GuaranteedCorrectScoreTips.com.'
                ]
            ],
            [
                '@type' => 'Question',
                'name' => 'How does the AI expected goals (xG) engine calculate exact score predictions?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'Our engine calculates attacking and defensive strength parameters for both home and away clubs, combines them with historical league averages, and constructs a 6x6 score probability matrix to determine the single most probable exact full-time scorelines.'
                ]
            ],
            [
                '@type' => 'Question',
                'name' => 'Are correct score predictions and tips updated every day?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'Yes, all fixtures across the Premier League, La Liga, Serie A, Bundesliga, Ligue 1, UEFA Champions League, and top global leagues are updated daily with fresh probabilities, odds calculations, and match previews.'
                ]
            ],
            [
                '@type' => 'Question',
                'name' => 'How can I unlock VIP & high-odds correct score multiples?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'You can access all daily high-conviction ranked picks, full Top 10 confidence lists, human expert picks, and Telegram instant alerts by upgrading to the PRO VIP plan on GuaranteedCorrectScoreTips.com.'
                ]
            ]
        ]
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endsection

@section('content')
    <!-- Top Header Ad Placement (Gated for Subscribers) -->
    <x-ad-banner type="header" />

    <!-- Telegram Channel & Admin Support Banner -->
    <x-telegram-banner />

    <!-- Hero Section -->
    @if(\App\Models\Setting::get('home_hero_section_enabled', '1') === '1')
    <section class="relative py-10 md:py-14 text-center max-w-4xl mx-auto">
        <div class="inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-full bg-blue-500/10 border border-blue-500/20 text-[#38BDF8] text-xs font-semibold mb-6">
            <span class="w-2 h-2 rounded-full bg-[#38BDF8] animate-pulse"></span>
            <span>GUARANTEED CORRECT SCORE TIPS • Poisson Probability Engine 2.4</span>
        </div>
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-white leading-tight">
            Guaranteed Correct Score Tips &amp; <span class="ai-gradient-text">AI Football Predictions</span>
        </h1>
        <p class="mt-4 text-base sm:text-lg text-slate-400 max-w-2xl mx-auto">
            Deterministic Expected-Goals (xG) statistical calculations paired with human expert insights. Verified daily exact score tips with zero guesswork.
        </p>

        <!-- Trust Stats Strip -->
        @if(\App\Models\Setting::get('home_hero_stats_enabled', '1') === '1')
            @php
                $listedMarkets = \App\Support\MarketRegistry::listed();
                $marketColors = [
                    'win'              => 'text-sky-400',
                    'over_2_5'         => 'text-emerald-400',
                    'gg'               => 'text-indigo-400',
                    'fh_over_0_5'      => 'text-teal-400',
                    'ht_win'           => 'text-violet-400',
                    'corners_over_8_5' => 'text-orange-400',
                    'cards_over_2_5'   => 'text-rose-400',
                ];
            @endphp
            <div class="mt-8 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 p-4 rounded-2xl glass-panel text-left">
                <div class="p-3">
                    <div class="text-xs text-slate-400 font-medium">Overall AI Hit Rate</div>
                    <div class="text-2xl font-extrabold text-[#38BDF8] mt-1">{{ $stats['ai']['overall']['rate'] ?? 0 }}%</div>
                    <div class="text-[10px] text-slate-500 mt-0.5">Calculated over {{ $stats['ai']['overall']['total'] ?? 0 }} fixtures</div>
                </div>
                @foreach($listedMarkets as $key => $definition)
                    <div class="p-3">
                        <div class="text-xs text-slate-400 font-medium">{{ $definition->short }} Market</div>
                        <div class="text-2xl font-extrabold {{ $marketColors[$key] ?? 'text-slate-300' }} mt-1">{{ $stats['ai'][$key]['rate'] ?? 0 }}%</div>
                        <div class="text-[10px] text-slate-500 mt-0.5">{{ $definition->label }}</div>
                    </div>
                @endforeach
                <div class="p-3">
                    <div class="text-xs text-slate-400 font-medium">Expert Analyst Accuracy</div>
                    <div class="text-2xl font-extrabold text-[#F5A623] mt-1">{{ $stats['expert']['overall']['rate'] ?? 0 }}%</div>
                    <div class="text-[10px] text-slate-500 mt-0.5">Verified human picks</div>
                </div>
            </div>
        @endif

        <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
            <a href="{{ route('top.picks') }}" class="px-6 py-3 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-bold shadow-lg shadow-sky-500/25 transition-all text-sm">
                Explore AI Top 10 Picks
            </a>
            <a href="{{ route('track-record') }}" class="px-6 py-3 rounded-xl bg-slate-800/90 hover:bg-slate-700 text-slate-200 font-semibold border border-slate-700/60 transition-all text-sm">
                View Full Track Record
            </a>
        </div>
    </section>
    @endif

    <!-- SportyBet Top 5 Slip - Hero Placement -->
    <div id="sportybet-slip" class="max-w-5xl mx-auto my-6">
        <x-sportybet-booking-code :picks="$aiTop5" />
    </div>

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
                            "{{ $featuredMatch->previewExcerpt(180) }}"
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
                        <a href="{{ $featuredMatch->canonicalUrl() }}" class="w-full sm:w-auto px-6 py-3.5 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-extrabold text-sm shadow-xl shadow-sky-500/25 transition-all text-center flex items-center justify-center space-x-2">
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
            <a href="{{ route('matches.index') }}" class="text-xs font-bold text-[#38BDF8] hover:text-white transition-colors bg-sky-500/10 hover:bg-sky-500/20 border border-sky-500/30 px-3 py-1.5 rounded-lg flex items-center gap-1">
                <span>View All Match Previews</span>
                <span>&rarr;</span>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($todayFixtures as $match)
                @php
                    $mainPred = $match->predictions->first();
                @endphp
                <div class="p-5 rounded-2xl glass-panel border border-slate-800/80 hover:border-sky-500/50 transition-all flex flex-col justify-between space-y-4 group">
                    <div>
                        <div class="flex items-center justify-between text-xs text-slate-400 mb-3">
                            <span class="font-semibold text-slate-300 truncate mr-2">{{ $match->league }}</span>
                            <span class="px-2.5 py-0.5 rounded-lg bg-slate-800/90 text-slate-300 border border-slate-700/60 font-mono text-[11px] shrink-0">
                                {{ $match->kickoff_at ? $match->kickoff_at->format('M d, H:i') : 'Upcoming' }}
                            </span>
                        </div>

                        <div class="flex flex-col items-center gap-1.5 py-2">
                            <div class="flex items-center gap-2 w-full min-w-0">
                                <x-team-crest :team="$match->homeClub" :name="$match->home_team" size="sm" />
                                <div class="font-bold text-base text-white group-hover:text-[#38BDF8] transition-colors truncate">
                                    {{ $match->home_team }}
                                </div>
                            </div>
                            <div class="text-[10px] font-mono text-slate-500 tracking-widest uppercase self-center">vs</div>
                            <div class="flex items-center gap-2 w-full min-w-0">
                                <x-team-crest :team="$match->awayClub" :name="$match->away_team" size="sm" />
                                <div class="font-bold text-base text-white group-hover:text-[#38BDF8] transition-colors truncate">
                                    {{ $match->away_team }}
                                </div>
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

                    <a href="{{ $match->canonicalUrl() }}" class="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-[#38BDF8] font-bold text-xs border border-slate-800 text-center transition-colors flex items-center justify-center space-x-1.5">
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

        @if($todayFixtures->count() > 0)
            <div class="mt-6 text-center">
                <a href="{{ route('matches.index') }}" class="inline-flex items-center space-x-2 px-6 py-3 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-[#38BDF8] hover:text-white border border-slate-700 font-extrabold text-xs transition-all shadow-lg hover:border-sky-500/50">
                    <span>Browse All Upcoming Match Previews &amp; Predictions</span>
                    <span>&rarr;</span>
                </a>
            </div>
        @endif
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

            <!-- SportyBet Top 5 Booking Code Integration -->
            @if($aiTop5->isNotEmpty())
                <div class="relative z-10 mb-6">
                    <x-sportybet-booking-code :picks="$aiTop5" />
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 relative z-10">
                @forelse($aiTop5 as $top)
                    @if($top->match)
                        <div class="p-5 rounded-2xl bg-[#0B0F17]/90 border border-slate-800 hover:border-sky-500/50 transition-all flex flex-col justify-between space-y-3 shadow-lg group">
                            <div>
                                <div class="flex items-center justify-between text-xs text-slate-400 mb-2">
                                    <span class="font-medium text-slate-400 truncate mr-2">{{ $top->match->league }}</span>
                                    <span class="text-slate-500 font-mono shrink-0">{{ $top->match->kickoff_at ? $top->match->kickoff_at->format('M d, H:i') : 'Upcoming' }}</span>
                                </div>
                                <div class="font-bold text-slate-100 text-sm mb-3 group-hover:text-sky-300 transition-colors">
                                    {{ $top->match->home_team }} vs {{ $top->match->away_team }}
                                </div>
                                <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 mb-3">
                                    <div class="min-w-0 mr-2">
                                        <div class="text-[10px] text-slate-400 uppercase font-semibold">Market / Pick</div>
                                        <div class="text-xs font-bold text-[#38BDF8] truncate">{{ uppercase_market($top->market) }} • {{ $top->pick }}</div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div class="text-[10px] text-slate-400 uppercase font-semibold">Probability</div>
                                        <div class="text-sm font-extrabold text-emerald-400 font-mono">{{ round($top->probability * 100, 1) }}%</div>
                                    </div>
                                </div>
                                @if(filled($top->rationale))
                                    <p class="text-xs text-slate-400 italic line-clamp-2">"{{ $top->rationale }}"</p>
                                @endif
                            </div>
                            <a href="{{ $top->match->canonicalUrl() }}" class="text-xs text-[#38BDF8] hover:underline font-bold pt-2 border-t border-slate-800/80 flex items-center justify-between">
                                <span>Read Match Preview & Stats</span>
                                <span>&rarr;</span>
                            </a>
                        </div>
                    @endif
                @empty
                    <div class="col-span-full p-8 rounded-2xl bg-[#0B0F17] text-center text-slate-400 text-sm">
                        No top conviction picks selected yet. Run the prediction pipeline.
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Newsroom strip. Only rendered when something is published, so an empty
         blog does not leave a dead section on the front page. --}}
    @if($latestPosts->isNotEmpty())
        <section class="space-y-4 mt-10">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-extrabold text-white">Latest News</h2>
                    <p class="text-xs text-slate-400">Analysis, fixture context and platform announcements.</p>
                </div>
                <a href="{{ route('blog.index') }}" class="text-xs font-semibold text-[#38BDF8] hover:underline">All News &rarr;</a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($latestPosts as $post)
                    <a href="{{ route('blog.show', $post) }}" class="p-5 rounded-2xl glass-panel border border-slate-800/80 hover:border-sky-500/50 transition-all group">
                        <div class="flex items-center gap-2 mb-2">
                            @if($post->isAiWritten())
                                <span class="px-2 py-0.5 rounded-full bg-[#38BDF8]/10 text-[#38BDF8] border border-[#38BDF8]/30 text-[10px] font-bold">AI</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full bg-[#F5A623]/10 text-[#F5A623] border border-[#F5A623]/30 text-[10px] font-bold">EDITORIAL</span>
                            @endif
                            <span class="text-[10px] uppercase tracking-wider text-slate-500 font-bold">{{ $post->categoryLabel() }}</span>
                        </div>
                        <div class="font-bold text-sm text-white group-hover:text-[#38BDF8] transition-colors leading-snug">{{ $post->title }}</div>
                        <p class="text-xs text-slate-400 mt-2 line-clamp-2">{{ $post->summary(120) }}</p>
                        <div class="text-[10px] text-slate-500 mt-3">{{ $post->published_at?->format('d M Y') }}</div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <!-- SEO AUTHORITY & EXACT MATCH CONTENT GUIDE -->
    <section class="mt-14 space-y-8">
        <div class="p-6 sm:p-10 rounded-3xl glass-panel border border-slate-800 space-y-8 relative overflow-hidden">
            <div class="space-y-3">
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-sky-500/10 text-sky-400 border border-sky-500/30 text-xs font-bold">
                    <span>⚽ #1 SOURCE FOR VERIFIED FOOTBALL PREDICTIONS</span>
                </div>
                <h2 class="text-2xl sm:text-3xl font-black text-white">
                    Guaranteed Correct Score Tips &amp; Accurate Football Predictions Today
                </h2>
                <p class="text-sm text-slate-300 leading-relaxed max-w-4xl">
                    Welcome to <strong>GuaranteedCorrectScoreTips.com</strong>, the premier destination for serious sports bettors and football enthusiasts looking for daily mathematical precision. By combining bivariate <strong>Poisson Expected-Goals (xG) matrix modeling</strong> with independent insights from verified human handicappers, our system delivers high-probability <strong>exact score tips</strong>, match winner picks, Over 2.5 goal forecasts, and Both Teams To Score (BTTS/GG) recommendations.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4 border-t border-slate-800/80">
                <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-2">
                    <div class="text-2xl font-black text-[#38BDF8]">01.</div>
                    <h3 class="font-extrabold text-base text-white">Poisson xG Exact Score Matrix</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Instead of subjective guesswork, our algorithm simulates thousands of match iterations calculating team attacking intensity, defensive resilience, and home pitch bias to isolate the most probable exact scorelines.
                    </p>
                </div>

                <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-2">
                    <div class="text-2xl font-black text-[#F5A623]">02.</div>
                    <h3 class="font-extrabold text-base text-white">Verified Human Handicappers</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Human strategists analyze live squad rotations, dressing room dynamics, tactical matchups, and weather conditions to publish transparent, audited tips alongside the AI engine.
                    </p>
                </div>

                <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-2">
                    <div class="text-2xl font-black text-[#22C55E]">03.</div>
                    <h3 class="font-extrabold text-base text-white">Audited Settlement &amp; Track Record</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Every single prediction is cryptographically locked prior to kickoff and resolved against official full-time scorelines, maintaining an immutable, transparent hit rate track record.
                    </p>
                </div>
            </div>

            <!-- Frequently Asked Questions (FAQ) with Schema Alignment -->
            <div class="space-y-4 pt-6 border-t border-slate-800/80">
                <h3 class="text-xl font-black text-white flex items-center gap-2">
                    <span>Frequently Asked Questions</span>
                    <span class="text-xs px-2.5 py-0.5 rounded-full bg-sky-500/10 text-sky-400 border border-sky-500/30">FAQ</span>
                </h3>

                <div class="space-y-3">
                    <details class="group p-4 rounded-2xl bg-slate-900/80 border border-slate-800 hover:border-slate-700 transition-all">
                        <summary class="font-bold text-sm text-slate-200 cursor-pointer flex items-center justify-between">
                            <span>What are guaranteed correct score tips?</span>
                            <span class="text-sky-400 font-mono transition-transform group-open:rotate-180">▼</span>
                        </summary>
                        <p class="text-xs text-slate-400 leading-relaxed mt-3 pt-3 border-t border-slate-800">
                            Guaranteed correct score tips represent high-conviction mathematical predictions for the exact final score of a football match (e.g. 2-1, 1-0, 2-0). At GuaranteedCorrectScoreTips.com, each exact score forecast is calculated using Poisson probability distribution algorithms and verified historical metrics.
                        </p>
                    </details>

                    <details class="group p-4 rounded-2xl bg-slate-900/80 border border-slate-800 hover:border-slate-700 transition-all">
                        <summary class="font-bold text-sm text-slate-200 cursor-pointer flex items-center justify-between">
                            <span>How does the AI model calculate exact scorelines?</span>
                            <span class="text-sky-400 font-mono transition-transform group-open:rotate-180">▼</span>
                        </summary>
                        <p class="text-xs text-slate-400 leading-relaxed mt-3 pt-3 border-t border-slate-800">
                            The platform processes club offensive xG, defensive concessions, home advantage multipliers, and recent form against league baselines. It produces a complete 6×6 score probability grid to identify the scoreline with the highest statistical likelihood.
                        </p>
                    </details>

                    <details class="group p-4 rounded-2xl bg-slate-900/80 border border-slate-800 hover:border-slate-700 transition-all">
                        <summary class="font-bold text-sm text-slate-200 cursor-pointer flex items-center justify-between">
                            <span>Which football leagues are covered daily?</span>
                            <span class="text-sky-400 font-mono transition-transform group-open:rotate-180">▼</span>
                        </summary>
                        <p class="text-xs text-slate-400 leading-relaxed mt-3 pt-3 border-t border-slate-800">
                            We cover the English Premier League, Spanish La Liga, Italian Serie A, German Bundesliga, French Ligue 1, UEFA Champions League, Europa League, and major global domestic competitions every single matchday.
                        </p>
                    </details>

                    <details class="group p-4 rounded-2xl bg-slate-900/80 border border-slate-800 hover:border-slate-700 transition-all">
                        <summary class="font-bold text-sm text-slate-200 cursor-pointer flex items-center justify-between">
                            <span>How do I get access to VIP Correct Score Tips &amp; Multiples?</span>
                            <span class="text-sky-400 font-mono transition-transform group-open:rotate-180">▼</span>
                        </summary>
                        <p class="text-xs text-slate-400 leading-relaxed mt-3 pt-3 border-t border-slate-800">
                            You can unlock the full Top 10 high-conviction ranked picks, human analyst picks, 100% ad-free browsing, and real-time Telegram alerts by joining our PRO VIP tier on the <a href="{{ route('subscription.pricing') }}" class="text-sky-400 underline font-semibold">Pricing &amp; VIP page</a>.
                        </p>
                    </details>
                </div>
            </div>
        </div>
    </section>
@endsection
