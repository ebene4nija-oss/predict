@extends('layouts.app')

@section('title', $match->home_team . ' vs ' . $match->away_team . ' Prediction & AI Match Preview — ' . $match->league)
@section('meta_description', 'Comprehensive ' . $match->home_team . ' vs ' . $match->away_team . ' prediction, Poisson expected-goals (xG) statistics, tactical preview, and betting odds breakdown for ' . $match->league . ' kickoff ' . $match->kickoff_at->format('M d, Y H:i') . '.')
@section('meta_keywords', strtolower($match->home_team . ' vs ' . $match->away_team . ' prediction, ' . $match->home_team . ' vs ' . $match->away_team . ' betting tips, ' . $match->league . ' AI preview, expected goals xG'))

@section('schema_json_ld')
@php
    // Built with json_encode rather than written inline: a literal "@context"
    // in the markup is compiled as Blade's @context directive and breaks the
    // whole page, and json_encode also escapes team names correctly.
    $fixture = $match->home_team . ' vs ' . $match->away_team;

    $schema = [
        [
            '@context' => 'https://schema.org',
            '@type' => 'SportsEvent',
            'name' => $fixture,
            'startDate' => $match->kickoff_at->toIso8601String(),
            'sport' => 'Soccer',
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
            'description' => 'Tactical AI match narrative preview and Poisson Expected-Goals (xG) probability predictions for ' . $fixture . '.',
        ],
        [
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => $fixture . ' AI Match Preview & Prediction',
            'description' => $fixture . ' expected goals (xG) tactical preview and AI match analysis.',
            'datePublished' => ($match->created_at ?? now())->toIso8601String(),
            'dateModified' => ($match->updated_at ?? now())->toIso8601String(),
            'author' => [
                '@type' => 'Organization',
                'name' => 'Guaranteed Correct Analytics Engine',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'GUARANTEED CORRECT',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset('images/logo.png'),
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
        <!-- Back Navigation -->
        <div class="flex items-center justify-between">
            <a href="{{ route('home') }}" class="inline-flex items-center space-x-2 text-xs font-bold text-slate-400 hover:text-[#38BDF8] transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Back to Home & Fixtures</span>
            </a>
            <span class="px-3 py-1 rounded-full bg-blue-500/10 text-[#38BDF8] border border-blue-500/20 text-xs font-bold font-mono">
                MATCH PREVIEW ID #{{ $match->id }}
            </span>
        </div>

        <!-- Prominent Match Header Hero -->
        <div class="p-6 sm:p-10 rounded-3xl bg-gradient-to-br from-[#151A24] via-[#121722] to-[#0B0F17] border-2 border-sky-500/30 shadow-2xl relative overflow-hidden text-center">
            <div class="absolute top-0 right-0 w-80 h-80 bg-sky-500/10 rounded-full blur-3xl -z-0"></div>

            <div class="inline-flex items-center space-x-2 px-4 py-1.5 rounded-full bg-slate-800/90 border border-slate-700 text-xs font-semibold text-slate-300 mb-6 relative z-10">
                <span class="w-2 h-2 rounded-full bg-[#38BDF8] animate-ping"></span>
                <span class="font-bold text-white">{{ $match->league }}</span>
                <span>•</span>
                <span class="text-[#38BDF8] font-mono font-bold">{{ $match->kickoff_at->format('D, M d, H:i') }} GMT</span>
            </div>

            <!-- Team vs Team Showcase -->
            <div class="grid grid-cols-3 items-center gap-4 py-4 relative z-10">
                <!-- Home Team -->
                <div class="flex flex-col items-center space-y-3">
                    <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl bg-gradient-to-br from-slate-800 to-slate-900 border-2 border-sky-500/40 flex items-center justify-center text-2xl font-black text-sky-400 shadow-2xl shadow-sky-500/10">
                        {{ strtoupper(substr($match->home_team, 0, 3)) }}
                    </div>
                    <span class="font-black text-lg sm:text-2xl text-white tracking-tight">{{ $match->home_team }}</span>
                    <span class="px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-800 text-[11px] text-slate-400 font-mono">
                        Avg {{ is_array($match->home_form) ? ($match->home_form['gf'] ?? '1.8') : '1.8' }} GF/match
                    </span>
                </div>

                <!-- Matchday Badge -->
                <div class="flex flex-col items-center justify-center">
                    <div class="text-3xl font-black text-slate-700 font-mono tracking-widest">VS</div>
                    <div class="mt-2 px-3 py-1 rounded-full bg-sky-500/10 text-[#38BDF8] border border-sky-500/20 text-[10px] uppercase font-bold tracking-wider">
                        FEATURED MATCH
                    </div>
                </div>

                <!-- Away Team -->
                <div class="flex flex-col items-center space-y-3">
                    <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl bg-gradient-to-br from-slate-800 to-slate-900 border-2 border-indigo-500/40 flex items-center justify-center text-2xl font-black text-indigo-400 shadow-2xl shadow-indigo-500/10">
                        {{ strtoupper(substr($match->away_team, 0, 3)) }}
                    </div>
                    <span class="font-black text-lg sm:text-2xl text-white tracking-tight">{{ $match->away_team }}</span>
                    <span class="px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-800 text-[11px] text-slate-400 font-mono">
                        Avg {{ is_array($match->away_form) ? ($match->away_form['gf'] ?? '1.4') : '1.4' }} GF/match
                    </span>
                </div>
            </div>

            <!-- Key Match Data Grid Strip -->
            <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 gap-3 text-left relative z-10">
                <div class="p-3.5 rounded-xl bg-slate-900/90 border border-slate-800 flex items-start space-x-3">
                    <span class="p-2 rounded-lg bg-sky-500/10 text-sky-400 text-xs font-bold">H2H</span>
                    <div>
                        <div class="text-[10px] text-slate-500 font-bold uppercase">Head to Head History</div>
                        <div class="text-xs font-semibold text-slate-200 mt-0.5">{{ $match->h2h_summary ?? 'Balanced recent record.' }}</div>
                    </div>
                </div>
                <div class="p-3.5 rounded-xl bg-slate-900/90 border border-slate-800 flex items-start space-x-3">
                    <span class="p-2 rounded-lg bg-amber-500/10 text-amber-400 text-xs font-bold">NEWS</span>
                    <div>
                        <div class="text-[10px] text-slate-500 font-bold uppercase">Squad Updates & Absences</div>
                        <div class="text-xs font-semibold text-slate-200 mt-0.5">{{ $match->injury_notes ?? 'No critical absences reported.' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PROMINENT AI MATCH PREVIEW SECTION -->
        <article class="p-6 sm:p-8 rounded-3xl bg-[#151A24] border-2 border-sky-500/40 shadow-2xl space-y-4 relative overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-sky-500 to-blue-600 flex items-center justify-center text-white shadow-lg shadow-sky-500/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <div class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full bg-[#38BDF8]/10 text-[#38BDF8] border border-[#38BDF8]/30 text-[10px] font-extrabold uppercase tracking-wider">
                            <span>GUARANTEED CORRECT EDITORIAL</span>
                        </div>
                        <h1 class="text-xl sm:text-2xl font-extrabold text-white mt-0.5">
                            {{ $match->home_team }} vs {{ $match->away_team }} Prediction & Tactical Match Preview
                        </h1>
                    </div>
                </div>
                <span class="hidden sm:inline-block text-xs font-mono text-slate-400 bg-slate-900 px-3 py-1 rounded-lg border border-slate-800">
                    Engine: Gemini Narrative + Poisson xG
                </span>
            </div>

            <!-- Preview Text Content -->
            <div class="p-5 rounded-2xl bg-[#0B0F17]/90 border border-slate-800/80 text-sm sm:text-base leading-relaxed text-slate-200 whitespace-pre-line relative">
                <span class="text-3xl text-sky-500/40 font-serif leading-none absolute top-2 left-3 select-none">“</span>
                <div class="pl-4">
                    {{ $match->preview_text ?? 'Tactical match analysis will update shortly.' }}
                </div>
            </div>
        </article>

        <!-- AI PREDICTION PROBABILITY PANEL -->
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
