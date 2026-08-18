@extends('layouts.app')

@section('title', $match->seoTitle())
@section('meta_description', $match->seoDescription())
@section('meta_keywords', $match->seoKeywords())
@section('canonical', $match->canonicalUrl())
@section('og_type', 'article')

@section('schema_json_ld')
@php
    $fixture = $match->home_team . ' vs ' . $match->away_team;
    $dateIso = $match->kickoff_at ? $match->kickoff_at->toIso8601String() : now()->toIso8601String();
    $createdIso = ($match->created_at ?? now())->toIso8601String();
    $updatedIso = ($match->updated_at ?? now())->toIso8601String();

    // Top prediction for FAQ schema
    $topPred = $match->predictions->sortByDesc('probability')->first();
    $topPickText = $topPred ? "{$topPred->pick} in {$topPred->market} with " . round($topPred->probability * 100, 1) . "% model confidence" : "a tightly contested outcome";

    $schema = [
        [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => route('home'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Predictions',
                    'item' => route('top.picks'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $fixture,
                    'item' => $match->canonicalUrl(),
                ],
            ],
        ],
        [
            '@context' => 'https://schema.org',
            '@type' => 'SportsEvent',
            'name' => $fixture . ' — ' . $match->league,
            'startDate' => $dateIso,
            'sport' => 'Soccer',
            'eventStatus' => 'https://schema.org/EventScheduled',
            'location' => [
                '@type' => 'Place',
                'name' => $match->league,
            ],
            'homeTeam' => [
                '@type' => 'SportsTeam',
                'name' => $match->home_team,
            ],
            'awayTeam' => [
                '@type' => 'SportsTeam',
                'name' => $match->away_team,
            ],
            'description' => $match->seoDescription(),
        ],
        [
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => $match->previewHeadline(),
            'description' => $match->seoDescription(),
            'datePublished' => $createdIso,
            'dateModified' => $updatedIso,
            'wordCount' => $match->previewWordCount(),
            'keywords' => $match->seoKeywords(),
            'author' => [
                '@type' => 'Organization',
                'name' => $match->isCustomPreview() ? 'Guaranteed Correct Senior Tactical Editors' : 'Guaranteed Correct AI Analytics Engine',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'GUARANTEED CORRECT',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset('images/logo.png'),
                ],
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => url()->current(),
            ],
        ],
        [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => [
                [
                    '@type' => 'Question',
                    'name' => "Who is predicted to win in {$match->home_team} vs {$match->away_team}?",
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => "Poisson expected goals (xG) analytics and mathematical models project {$topPickText} for {$match->home_team} vs {$match->away_team} in {$match->league}.",
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name' => "What is the tactical preview and xG analysis for {$match->home_team} vs {$match->away_team}?",
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => "{$match->home_team} average " . (is_array($match->home_form) ? ($match->home_form['gf'] ?? '1.8') : '1.8') . " GF/match at home while {$match->away_team} average " . (is_array($match->away_form) ? ($match->away_form['gf'] ?? '1.4') : '1.4') . " GF/match. Head-to-head dynamic: " . ($match->h2h_summary ?: 'Balanced recent record.') . ".",
                    ],
                ],
            ],
        ],
    ];
@endphp
<script type="application/ld+json">
{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
</script>
@endsection

@section('content')
    <x-ad-banner type="header" />

    <div class="max-w-4xl mx-auto space-y-8">
        <!-- Back Navigation & Admin Quick Link -->
        <div class="flex items-center justify-between">
            <a href="{{ route('home') }}" class="inline-flex items-center space-x-2 text-xs font-bold text-slate-400 hover:text-[#38BDF8] transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Back to Home & Fixtures</span>
            </a>

            <div class="flex items-center space-x-2">
                @auth
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.previews.edit', $match) }}"
                           class="px-3 py-1 rounded-xl bg-sky-500/10 hover:bg-sky-500/20 text-[#38BDF8] border border-sky-500/30 text-xs font-bold font-mono flex items-center space-x-1.5 transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            <span>Admin: Edit Preview & SEO</span>
                        </a>
                    @endif
                @endauth
                <span class="px-3 py-1 rounded-full bg-blue-500/10 text-[#38BDF8] border border-blue-500/20 text-xs font-bold font-mono">
                    MATCH #{{ $match->id }}
                </span>
            </div>
        </div>

        @if($match->isDraftPreview())
            <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-bold flex items-center justify-between">
                <span>⚠️ Admin Notice: This match preview is currently in Draft status and will not be displayed to regular public visitors.</span>
                <a href="{{ route('admin.previews.edit', $match) }}" class="underline hover:text-amber-200">Publish Now</a>
            </div>
        @endif

        <!-- Prominent Match Header Hero -->
        <div class="p-4 sm:p-10 rounded-2xl sm:rounded-3xl bg-gradient-to-br from-[#151A24] via-[#121722] to-[#0B0F17] border-2 border-sky-500/30 shadow-2xl relative overflow-hidden text-center">
            <div class="absolute top-0 right-0 w-80 h-80 bg-sky-500/10 rounded-full blur-3xl -z-0"></div>

            <div class="inline-flex items-center space-x-2 px-4 py-1.5 rounded-full bg-slate-800/90 border border-slate-700 text-xs font-semibold text-slate-300 mb-6 relative z-10">
                <span class="w-2 h-2 rounded-full bg-[#38BDF8] animate-ping"></span>
                <span class="font-bold text-white">{{ $match->league }}</span>
                <span>•</span>
                <span class="text-[#38BDF8] font-mono font-bold">{{ $match->kickoff_at ? $match->kickoff_at->format('D, M d, H:i') : 'TBD' }} GMT</span>
            </div>

            <!-- Team vs Team Showcase -->
            <div class="grid grid-cols-3 items-center gap-2 sm:gap-4 py-4 relative z-10">
                <!-- Home Team -->
                <div class="flex flex-col items-center space-y-2 sm:space-y-3">
                    <div class="w-16 h-16 sm:w-24 sm:h-24 rounded-2xl bg-gradient-to-br from-slate-800 to-slate-900 border-2 border-sky-500/40 flex items-center justify-center text-xl sm:text-2xl font-black text-sky-400 shadow-2xl shadow-sky-500/10 p-2 sm:p-3">
                        @if($match->crestFor('home'))
                            <img src="{{ $match->crestFor('home') }}" alt="{{ $match->home_team }} crest" class="w-full h-full object-contain">
                        @else
                            {{ strtoupper(substr($match->home_team, 0, 3)) }}
                        @endif
                    </div>
                    <span class="font-black text-base sm:text-2xl text-white tracking-tight text-center">{{ $match->home_team }}</span>
                    <span class="px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg bg-slate-900 border border-slate-800 text-[10px] sm:text-[11px] text-slate-400 font-mono">
                        Avg {{ is_array($match->home_form) ? ($match->home_form['gf'] ?? '1.8') : '1.8' }} GF/m
                    </span>
                </div>

                <!-- Matchday Badge -->
                <div class="flex flex-col items-center justify-center">
                    <div class="text-2xl sm:text-3xl font-black text-slate-700 font-mono tracking-widest">VS</div>
                    <div class="mt-2 px-2.5 py-1 rounded-full bg-sky-500/10 text-[#38BDF8] border border-sky-500/20 text-[9px] sm:text-[10px] uppercase font-bold tracking-wider">
                        FEATURED MATCH
                    </div>
                </div>

                <!-- Away Team -->
                <div class="flex flex-col items-center space-y-2 sm:space-y-3">
                    <div class="w-16 h-16 sm:w-24 sm:h-24 rounded-2xl bg-gradient-to-br from-slate-800 to-slate-900 border-2 border-indigo-500/40 flex items-center justify-center text-xl sm:text-2xl font-black text-indigo-400 shadow-2xl shadow-indigo-500/10 p-2 sm:p-3">
                        @if($match->crestFor('away'))
                            <img src="{{ $match->crestFor('away') }}" alt="{{ $match->away_team }} crest" class="w-full h-full object-contain">
                        @else
                            {{ strtoupper(substr($match->away_team, 0, 3)) }}
                        @endif
                    </div>
                    <span class="font-black text-base sm:text-2xl text-white tracking-tight text-center">{{ $match->away_team }}</span>
                    <span class="px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg bg-slate-900 border border-slate-800 text-[10px] sm:text-[11px] text-slate-400 font-mono">
                        Avg {{ is_array($match->away_form) ? ($match->away_form['gf'] ?? '1.4') : '1.4' }} GF/m
                    </span>
                </div>
            </div>

            <!-- Key Match Data Grid Strip -->
            <div class="mt-6 sm:mt-8 grid grid-cols-1 sm:grid-cols-2 gap-3 text-left relative z-10">
                <div class="p-3.5 rounded-xl bg-slate-900/90 border border-slate-800 flex items-start space-x-3">
                    <span class="p-2 rounded-lg bg-sky-500/10 text-sky-400 text-xs font-bold shrink-0">H2H</span>
                    <div>
                        <div class="text-[10px] text-slate-500 font-bold uppercase">Head to Head History</div>
                        <div class="text-xs font-semibold text-slate-200 mt-0.5">{{ $match->h2h_summary ?? 'Balanced recent record.' }}</div>
                    </div>
                </div>
                <div class="p-3.5 rounded-xl bg-slate-900/90 border border-slate-800 flex items-start space-x-3">
                    <span class="p-2 rounded-lg bg-amber-500/10 text-amber-400 text-xs font-bold shrink-0">NEWS</span>
                    <div>
                        <div class="text-[10px] text-slate-500 font-bold uppercase">Squad Updates & Absences</div>
                        <div class="text-xs font-semibold text-slate-200 mt-0.5">{{ $match->injury_notes ?? 'No critical absences reported.' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PROMINENT COMPREHENSIVE AI MATCH PREVIEW SECTION -->
        <article class="p-4 sm:p-10 rounded-2xl sm:rounded-3xl bg-[#151A24] border-2 border-sky-500/40 shadow-2xl space-y-6 relative overflow-hidden">
            <!-- Background Glow Effect -->
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-sky-500/10 rounded-full blur-3xl pointer-events-none -z-0"></div>

            <header class="border-b border-slate-800 pb-5 sm:pb-6 space-y-4 relative z-10">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-start sm:items-center space-x-3 sm:space-x-3.5">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-gradient-to-tr from-sky-500 to-indigo-600 flex items-center justify-center text-white shadow-lg shadow-sky-500/25 flex-shrink-0 mt-0.5 sm:mt-0 ring-2 ring-sky-400/20">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div>
                            <div class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full bg-[#38BDF8]/10 text-[#38BDF8] border border-[#38BDF8]/30 text-[10px] font-extrabold uppercase tracking-wider">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#38BDF8] animate-pulse"></span>
                                <span>{{ $match->isCustomPreview() ? 'SENIOR TACTICAL EDITORIAL' : 'GUARANTEED CORRECT EDITORIAL & AI ANALYSIS' }}</span>
                            </div>
                            <h1 class="text-lg sm:text-2xl lg:text-3xl font-black text-white mt-1.5 leading-tight tracking-tight">
                                {{ $match->previewHeadline() }}
                            </h1>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2 self-start sm:self-auto shrink-0">
                        <span class="text-xs font-mono text-slate-300 bg-slate-900/90 px-3 py-1.5 rounded-xl border border-slate-800 flex items-center space-x-1.5 shadow-sm">
                            <svg class="w-3.5 h-3.5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>{{ $match->previewWordCount() }} words · ~{{ $match->previewReadingMinutes() }} min read</span>
                        </span>
                    </div>
                </div>

                <!-- Structured Key Section Quick Chips -->
                @if(filled($match->preview_text))
                    <div class="pt-1 sm:pt-2 flex items-center flex-wrap gap-1.5 sm:gap-2 text-xs">
                        <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider mr-1 flex items-center space-x-1">
                            <svg class="w-3.5 h-3.5 text-[#38BDF8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                            <span>Sections:</span>
                        </span>
                        <span class="px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg bg-slate-900/80 border border-slate-800 text-slate-300 font-medium text-[10px] sm:text-[11px]">⚡ Match Overview</span>
                        <span class="px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg bg-slate-900/80 border border-slate-800 text-slate-300 font-medium text-[10px] sm:text-[11px]">🎯 Tactical Breakdown</span>
                        <span class="px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg bg-slate-900/80 border border-slate-800 text-slate-300 font-medium text-[10px] sm:text-[11px]">📊 H2H & Form Guide</span>
                        <span class="px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg bg-slate-900/80 border border-slate-800 text-slate-300 font-medium text-[10px] sm:text-[11px]">🚑 Team News</span>
                        <span class="px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg bg-slate-900/80 border border-slate-800 text-slate-300 font-medium text-[10px] sm:text-[11px]">📈 Poisson xG Outlook</span>
                        <span class="px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg bg-slate-900/80 border border-slate-800 text-sky-400 font-bold text-[10px] sm:text-[11px]">🏆 Score Prediction</span>
                    </div>
                @endif
            </header>

            <!-- Preview Text Content Rendered (Markdown Formatted) -->
            <div class="p-3.5 sm:p-8 rounded-xl sm:rounded-2xl bg-[#0B0F17] border border-slate-800/90 text-sm sm:text-base leading-relaxed text-slate-200 relative overflow-x-auto">
                @if($match->previewIsPublishable() || (auth()->check() && auth()->user()->isAdmin()))
                    @if(filled($match->preview_text))
                        <div class="preview-markdown-content space-y-4">
                            {!! \Illuminate\Support\Str::markdown($match->preview_text) !!}
                        </div>
                    @else
                        <p class="text-slate-400 italic">No tactical preview text available for this fixture yet.</p>
                    @endif
                @else
                    @php $leadDays = \App\Models\GameMatch::previewLeadDays(); @endphp
                    <p class="text-slate-400">
                        The comprehensive tactical preview for this fixture will be published {{ $leadDays }} {{ \Illuminate\Support\Str::plural('day', $leadDays) }} before kickoff.
                    </p>
                @endif
            </div>

            <!-- Author / Editorial Stamp Footer -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 pt-3 text-xs text-slate-400 border-t border-slate-800/80">
                <div class="flex items-center space-x-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span>Published by <strong class="text-slate-200 font-bold">Guaranteed Correct Sports Intelligence</strong> · {{ $match->league }}</span>
                </div>
                <div class="font-mono text-[11px] text-slate-400 bg-slate-900/60 px-3 py-1 rounded-lg border border-slate-800">
                    Updated: {{ $match->preview_refreshed_at ? $match->preview_refreshed_at->format('M d, Y H:i') : ($match->preview_generated_at ? $match->preview_generated_at->format('M d, Y H:i') : 'Matchday Cycle') }}
                </div>
            </div>
        </article>

        <!-- Official Telegram Community Banner -->
        <x-telegram-banner />

        <!-- In-Content Ad Placement -->
        <x-ad-banner type="in-content" />

        <!-- AI PREDICTION PROBABILITY PANEL (GATED FOR PRO SUBSCRIBERS) -->
        @if($isSubscriber)
            <div class="p-6 sm:p-8 rounded-3xl bg-[#151A24] border-2 border-sky-500/30 shadow-2xl space-y-6 relative">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <span class="px-3 py-1 rounded-full bg-[#38BDF8]/10 text-[#38BDF8] border border-[#38BDF8]/30 text-xs font-extrabold flex items-center space-x-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            <span>GUARANTEED CORRECT CALCULATED PROBABILITIES</span>
                        </span>
                    </div>
                    <span class="text-xs text-slate-400 font-mono">Model: Poisson xG Matrix</span>
                </div>

                <!-- Market Probability Bars -->
                <div class="space-y-5">
                    @foreach($match->predictions as $pred)
                        <div class="space-y-2 p-4 rounded-2xl bg-slate-900/80 border border-slate-800">
                            <div class="flex items-center justify-between text-xs sm:text-sm">
                                <div class="flex items-center space-x-2">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-800 text-slate-300">
                                        {{ uppercase_mkt($pred->market) }}
                                    </span>
                                    <span class="font-bold text-white">
                                        {{ uppercase_market_name($pred->market) }}: <span class="text-[#38BDF8]">{{ $pred->pick }}</span>
                                    </span>
                                </div>
                                <span class="font-mono font-extrabold text-emerald-400 text-base">
                                    {{ round($pred->probability * 100, 1) }}% Confidence
                                </span>
                            </div>

                            <!-- Gradient Confidence Bar -->
                            <div class="w-full h-3.5 bg-slate-800 rounded-full overflow-hidden p-0.5 border border-slate-700/50">
                                <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 via-sky-400 to-indigo-500 transition-all duration-1000" style="width: {{ round($pred->probability * 100, 1) }}%"></div>
                            </div>
                            <div class="text-xs text-slate-400 italic">"{{ $pred->rationale }}"</div>
                        </div>
                    @endforeach
                </div>

                <!-- Dynamic Trust Note -->
                <div class="pt-4 border-t border-slate-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs text-slate-400">
                    <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>AI has achieved <strong class="text-emerald-400 font-mono">{{ $overallAiWinRate }}%</strong> accuracy across verified settled results this season.</span>
                    </div>
                    <a href="{{ route('track-record') }}" class="text-[#38BDF8] hover:underline font-bold whitespace-nowrap">View Public Audit Record &rarr;</a>
                </div>
            </div>
        @else
            <!-- GATED / LOCKED PREDICTION PROBABILITIES FOR FREE USERS -->
            <div class="relative p-6 sm:p-8 rounded-3xl bg-gradient-to-br from-[#151A24] via-[#121824] to-[#0A0F18] border-2 border-sky-500/30 shadow-2xl overflow-hidden space-y-6">
                <!-- Blurred Background Preview Content -->
                <div class="filter blur-md select-none pointer-events-none opacity-25 space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="h-6 w-72 bg-sky-500/20 rounded-full"></div>
                        <div class="h-4 w-32 bg-slate-800 rounded"></div>
                    </div>
                    @for($i = 1; $i <= 3; $i++)
                        <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-2">
                            <div class="flex justify-between">
                                <div class="h-4 w-48 bg-slate-700 rounded"></div>
                                <div class="h-4 w-24 bg-emerald-500/40 rounded"></div>
                            </div>
                            <div class="h-3.5 bg-slate-800 rounded-full w-full"></div>
                            <div class="h-3 w-64 bg-slate-800 rounded"></div>
                        </div>
                    @endfor
                </div>

                <!-- Paywall Overlay & Conversion CTA -->
                <div class="absolute inset-0 bg-[#0B0F17]/90 backdrop-blur-md flex flex-col items-center justify-center text-center p-6 sm:p-10 space-y-5 z-20">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-sky-500 to-indigo-600 flex items-center justify-center text-white text-2xl shadow-xl shadow-sky-500/30 ring-4 ring-sky-500/20">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>

                    <div class="space-y-2 max-w-lg">
                        <div class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full bg-amber-500/15 border border-amber-500/30 text-amber-400 text-xs font-black uppercase tracking-wider">
                            <span>👑 PRO VIP FEATURE</span>
                        </div>
                        <h3 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                            Calculated Probabilities &amp; xG Models Gated
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                            Upgrade to <strong class="text-white">Guaranteed Correct PRO</strong> to unlock full expected-goals (xG) Poisson calculations, exact score probabilities, and market conviction percentages for this match.
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
                        <a href="{{ route('subscription.pricing') }}" class="w-full sm:w-auto px-7 py-3.5 rounded-xl bg-gradient-to-r from-sky-500 via-blue-600 to-emerald-500 hover:from-sky-400 hover:to-emerald-400 text-white font-extrabold text-xs shadow-xl shadow-sky-500/30 transition-all text-center">
                            Upgrade to PRO to Unlock Calculated Probabilities &rarr;
                        </a>
                        <a href="{{ route('top.picks') }}" class="w-full sm:w-auto px-5 py-3.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-700 text-xs font-bold transition-all text-center">
                            View Free Top Picks
                        </a>
                    </div>

                    <div class="flex flex-wrap items-center justify-center gap-4 text-[11px] text-slate-400 pt-1">
                        <span class="flex items-center gap-1 text-emerald-400 font-semibold">✓ Instant Unlocking</span>
                        <span class="flex items-center gap-1 text-sky-400 font-semibold">✓ All 7 Market Matrixes</span>
                        <span class="flex items-center gap-1 text-amber-400 font-semibold">✓ 100% Ad-Free Access</span>
                    </div>
                </div>
            </div>
        @endif

        <!-- Expert Analysis Section -->
        @if($match->expertPicks->count() > 0)
            <div class="p-6 sm:p-8 rounded-3xl bg-[#151A24] border border-amber-500/40 space-y-4">
                <div class="flex items-center space-x-2">
                    <span class="px-3 py-1 rounded-full bg-[#F5A623]/10 text-[#F5A623] border border-[#F5A623]/30 text-xs font-extrabold flex items-center space-x-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <span>VERIFIED HUMAN EXPERT ANALYSIS</span>
                    </span>
                </div>

                @foreach($match->expertPicks as $ePick)
                    <div class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="flex items-center space-x-3">
                            <img src="{{ $ePick->expert->photo_path ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=150' }}" class="w-12 h-12 rounded-full border-2 border-amber-500/60 object-cover" alt="Expert">
                            <div>
                                <div class="font-extrabold text-sm text-slate-100">{{ $ePick->expert->name }}</div>
                                <div class="text-xs text-amber-400 font-bold">{{ uppercase_market_name($ePick->market) }}: {{ $ePick->pick }}</div>
                            </div>
                        </div>
                        <div class="text-xs text-slate-300 italic max-w-md bg-slate-950 p-3 rounded-xl border border-slate-800">
                            "{{ $ePick->rationale }}"
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
