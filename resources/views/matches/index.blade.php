@extends('layouts.app')

@section('title', 'Football Match Previews & AI Predictions Today — Guaranteed Correct Score Tips')
@section('meta_description', 'Explore in-depth match previews, Poisson expected goals (xG) statistics, probability models, and betting predictions for all upcoming football fixtures across top leagues.')
@section('meta_keywords', 'football match previews, soccer match preview, football predictions today, exact score previews, match analysis, soccer predictions, premier league previews, champions league match preview')
@section('canonical', route('matches.index'))

@section('schema_json_ld')
@php
    $items = [];
    foreach ($matches as $idx => $m) {
        $items[] = [
            '@type' => 'ListItem',
            'position' => $idx + 1,
            'name' => "{$m->home_team} vs {$m->away_team} - Match Preview & Predictions",
            'url' => $m->canonicalUrl(),
        ];
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => 'Football Match Previews & AI Predictions',
        'description' => 'Comprehensive tactical previews, Poisson expected goals (xG) statistics, and high-conviction football betting predictions for upcoming fixtures.',
        'url' => route('matches.index'),
        'mainEntity' => [
            '@type' => 'ItemList',
            'itemListElement' => $items,
        ],
    ];
@endphp
<script type="application/ld+json">
{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endsection

@section('content')
    <x-ad-banner type="header" />

    <div class="space-y-8">
        <!-- Page Header Banner -->
        <div class="p-6 sm:p-8 rounded-3xl bg-gradient-to-br from-[#151A24] via-[#121824] to-[#0A0F18] border border-sky-500/30 shadow-2xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-80 h-80 bg-sky-500/10 rounded-full blur-3xl -z-0"></div>

            <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <div class="space-y-2 max-w-2xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-3 py-1 rounded-full bg-[#38BDF8]/15 border border-[#38BDF8]/30 text-[#38BDF8] text-xs font-black uppercase tracking-wider flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-[#38BDF8] animate-pulse"></span>
                            Match Previews &amp; Tactical Insights
                        </span>
                        <span class="px-2.5 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-mono font-bold">
                            {{ $totalUpcomingMatches }} Fixtures Monitored
                        </span>
                    </div>
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight leading-tight">
                        Football Match Previews &amp; <span class="ai-gradient-text">AI Predictions</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-400 leading-relaxed">
                        In-depth pre-match previews, bivariate Poisson expected goals (xG) distributions, head-to-head records, and betting conviction ratings for all upcoming games.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3 shrink-0">
                    <a href="{{ route('top.picks') }}" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-extrabold text-xs shadow-lg shadow-sky-500/25 transition-all">
                        AI Top 10 Ranked Picks &rarr;
                    </a>
                    <a href="{{ route('expert.picks') }}" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-xs transition-colors">
                        Expert Analyst Picks
                    </a>
                </div>
            </div>
        </div>

        <!-- Official Telegram Community Banner -->
        <x-telegram-banner />

        <!-- SportyBet Top 5 Slip Integration -->
        @if($aiTop5->isNotEmpty())
            <x-sportybet-booking-code :picks="$aiTop5" />
        @endif

        <!-- Filter & Search Controls -->
        <div class="p-5 sm:p-6 rounded-2xl glass-panel border border-slate-800 space-y-4">
            <form method="GET" action="{{ route('matches.index') }}" class="space-y-4">
                <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
                    <!-- Search Box -->
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input type="text"
                               name="search"
                               value="{{ $search }}"
                               placeholder="Search team or league (e.g. Arsenal, Real Madrid, Premier League)..."
                               class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-900/90 border border-slate-700 text-white text-xs placeholder-slate-500 focus:border-sky-400 focus:outline-none focus:ring-1 focus:ring-sky-400 transition-all">
                    </div>

                    <!-- Time & Status Filters -->
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Date Filter Buttons -->
                        <div class="flex items-center rounded-xl bg-slate-900 p-1 border border-slate-800 text-xs">
                            <a href="{{ route('matches.index', array_merge(request()->query(), ['time' => 'all'])) }}"
                               class="px-3 py-1.5 rounded-lg font-bold transition-all {{ $timeFilter === 'all' ? 'bg-[#38BDF8] text-slate-950 shadow-sm' : 'text-slate-400 hover:text-white' }}">
                                All
                            </a>
                            <a href="{{ route('matches.index', array_merge(request()->query(), ['time' => 'today'])) }}"
                               class="px-3 py-1.5 rounded-lg font-bold transition-all {{ $timeFilter === 'today' ? 'bg-[#38BDF8] text-slate-950 shadow-sm' : 'text-slate-400 hover:text-white' }}">
                                Today
                            </a>
                            <a href="{{ route('matches.index', array_merge(request()->query(), ['time' => 'tomorrow'])) }}"
                               class="px-3 py-1.5 rounded-lg font-bold transition-all {{ $timeFilter === 'tomorrow' ? 'bg-[#38BDF8] text-slate-950 shadow-sm' : 'text-slate-400 hover:text-white' }}">
                                Tomorrow
                            </a>
                            <a href="{{ route('matches.index', array_merge(request()->query(), ['time' => 'weekend'])) }}"
                               class="px-3 py-1.5 rounded-lg font-bold transition-all {{ $timeFilter === 'weekend' ? 'bg-[#38BDF8] text-slate-950 shadow-sm' : 'text-slate-400 hover:text-white' }}">
                                Weekend
                            </a>
                        </div>

                        <!-- Status Selector -->
                        <select name="status" onchange="this.form.submit()" class="px-3 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs font-semibold focus:border-sky-400 focus:outline-none">
                            <option value="upcoming" @selected($status === 'upcoming')>Upcoming Fixtures</option>
                            <option value="finished" @selected($status === 'finished')>Finished Results</option>
                            <option value="all" @selected($status === 'all')>All Statuses</option>
                        </select>

                        <button type="submit" class="px-4 py-2.5 rounded-xl bg-sky-500 hover:bg-sky-400 text-slate-950 font-extrabold text-xs transition-colors">
                            Filter
                        </button>

                        @if(filled($search) || filled($selectedLeague) || $timeFilter !== 'all' || $status !== 'upcoming')
                            <a href="{{ route('matches.index') }}" class="px-3 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white text-xs font-bold transition-colors">
                                Reset
                            </a>
                        @endif
                    </div>
                </div>

                <!-- League Filter Pills -->
                @if($availableLeagues->isNotEmpty())
                    <div class="pt-3 border-t border-slate-800/80 flex items-center gap-2 overflow-x-auto no-scrollbar pb-1">
                        <span class="text-[11px] font-bold text-slate-500 uppercase shrink-0 mr-1">Leagues:</span>
                        <a href="{{ route('matches.index', array_merge(request()->query(), ['league' => 'all'])) }}"
                           class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap transition-all {{ empty($selectedLeague) || $selectedLeague === 'all' ? 'bg-sky-500/20 text-[#38BDF8] border border-sky-500/40' : 'bg-slate-900 text-slate-400 hover:text-slate-200 border border-slate-800' }}">
                            All Leagues ({{ $totalUpcomingMatches }})
                        </a>
                        @foreach($availableLeagues as $league)
                            <a href="{{ route('matches.index', array_merge(request()->query(), ['league' => $league->league])) }}"
                               class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap transition-all {{ $selectedLeague === $league->league ? 'bg-sky-500/20 text-[#38BDF8] border border-sky-500/40' : 'bg-slate-900 text-slate-400 hover:text-slate-200 border border-slate-800' }}">
                                {{ $league->league }} <span class="text-[10px] text-slate-500">({{ $league->count }})</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </form>
        </div>

        <!-- Matches Listing Grid -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-extrabold text-white flex items-center space-x-2">
                    <span>{{ $status === 'finished' ? 'Recent Match Results & Previews' : 'Upcoming Match Previews' }}</span>
                    <span class="text-xs font-normal text-slate-400">({{ $matches->total() }} matches found)</span>
                </h2>
                <span class="text-xs text-slate-400 hidden sm:inline">Click any match to view full Poisson xG preview</span>
            </div>

            @if($matches->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach($matches as $match)
                        @php
                            $bestPred = $match->predictions->sortByDesc('probability')->first();
                        @endphp
                        <div class="p-5 rounded-2xl glass-panel border border-slate-800 hover:border-sky-500/50 transition-all flex flex-col justify-between space-y-4 group shadow-lg">
                            <!-- Card Header: League & Kickoff -->
                            <div class="flex items-center justify-between text-xs text-slate-400 pb-3 border-b border-slate-800/80">
                                <span class="font-bold text-slate-300 truncate mr-2">{{ $match->league }}</span>
                                <span class="font-mono text-slate-400 shrink-0 text-[11px] bg-slate-900 px-2 py-0.5 rounded-md border border-slate-800">
                                    {{ $match->kickoff_at ? $match->kickoff_at->format('M d, H:i') : 'Upcoming' }}
                                </span>
                            </div>

                            <!-- Teams Row with Crests -->
                            <a href="{{ $match->canonicalUrl() }}" class="space-y-2 block">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2.5 min-w-0">
                                        @if($match->homeClub && $match->homeClub->crest_url)
                                            <img src="{{ $match->homeClub->crest_url }}" alt="{{ $match->home_team }}" class="w-6 h-6 object-contain shrink-0">
                                        @else
                                            <div class="w-6 h-6 rounded-full bg-slate-800 text-[10px] font-extrabold flex items-center justify-center text-slate-300 shrink-0">
                                                {{ substr($match->home_team, 0, 2) }}
                                            </div>
                                        @endif
                                        <span class="font-extrabold text-sm text-white truncate group-hover:text-sky-300 transition-colors">
                                            {{ $match->home_team }}
                                        </span>
                                    </div>
                                    @if($match->result && $match->result->home_score !== null)
                                        <span class="font-mono font-black text-base text-yellow-400">{{ $match->result->home_score }}</span>
                                    @endif
                                </div>

                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2.5 min-w-0">
                                        @if($match->awayClub && $match->awayClub->crest_url)
                                            <img src="{{ $match->awayClub->crest_url }}" alt="{{ $match->away_team }}" class="w-6 h-6 object-contain shrink-0">
                                        @else
                                            <div class="w-6 h-6 rounded-full bg-slate-800 text-[10px] font-extrabold flex items-center justify-center text-slate-300 shrink-0">
                                                {{ substr($match->away_team, 0, 2) }}
                                            </div>
                                        @endif
                                        <span class="font-extrabold text-sm text-white truncate group-hover:text-sky-300 transition-colors">
                                            {{ $match->away_team }}
                                        </span>
                                    </div>
                                    @if($match->result && $match->result->away_score !== null)
                                        <span class="font-mono font-black text-base text-yellow-400">{{ $match->result->away_score }}</span>
                                    @endif
                                </div>
                            </a>

                            <!-- AI Recommendation Bar -->
                            @if($bestPred)
                                <div class="p-3 rounded-xl bg-slate-900/90 border border-slate-800 flex items-center justify-between">
                                    <div class="min-w-0 mr-2">
                                        <div class="text-[10px] text-slate-500 uppercase font-bold">Top AI Conviction</div>
                                        <div class="text-xs font-extrabold text-[#38BDF8] truncate">{{ uppercase_market($bestPred->market) }} • {{ $bestPred->pick }}</div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div class="text-[10px] text-slate-500 uppercase font-bold">Probability</div>
                                        <div class="text-xs font-extrabold text-emerald-400 font-mono">{{ round($bestPred->probability * 100, 1) }}%</div>
                                    </div>
                                </div>
                            @endif

                            <!-- Preview Text Snippet -->
                            @if(filled($match->preview_text))
                                <p class="text-xs text-slate-400 line-clamp-2 leading-relaxed italic">
                                    {{ Str::limit(strip_tags($match->preview_text), 120) }}
                                </p>
                            @elseif(filled($match->preview_headline))
                                <p class="text-xs text-slate-300 font-medium line-clamp-1">
                                    {{ $match->preview_headline }}
                                </p>
                            @endif

                            <!-- Action CTA -->
                            <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between">
                                <span class="text-[11px] text-slate-500 font-medium">
                                    {{ $match->predictions->count() }} prediction markets
                                </span>
                                <a href="{{ $match->canonicalUrl() }}" class="inline-flex items-center space-x-1.5 text-xs font-extrabold text-[#38BDF8] group-hover:text-white transition-colors">
                                    <span>Read Preview &amp; Stats</span>
                                    <span>&rarr;</span>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- In-Content Ad Placement -->
                <x-ad-banner type="in-content" />

                <!-- Pagination -->
                <div class="mt-8">
                    {{ $matches->links() }}
                </div>
            @else
                <div class="p-12 rounded-3xl glass-panel text-center space-y-4 border border-slate-800">
                    <div class="w-16 h-16 rounded-full bg-slate-900 text-slate-500 flex items-center justify-center mx-auto text-2xl">
                        ⚽
                    </div>
                    <h3 class="text-lg font-bold text-white">No matches found for this filter</h3>
                    <p class="text-xs text-slate-400 max-w-md mx-auto">
                        Try resetting your search query, switching the date range, or selecting "All Leagues" to browse available previews.
                    </p>
                    <a href="{{ route('matches.index') }}" class="inline-block px-5 py-2 rounded-xl bg-sky-500 hover:bg-sky-400 text-slate-950 font-bold text-xs transition-colors">
                        View All Upcoming Matches
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
