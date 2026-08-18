@extends('layouts.app')

@section('title', 'Admin Dashboard — Guaranteed Correct Parameter Controls')

@section('content')
    <div class="max-w-6xl mx-auto space-y-8 py-4">
        {{-- Admin Navigation Bar --}}
        @include('admin.partials.nav')

        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm font-semibold">{{ session('success') }}</div>
        @endif

        <!-- Dashboard Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/30 text-xs font-bold mb-2">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/></svg>
                    <span>ADMINISTRATOR CONTROL PANEL</span>
                </div>
                <h1 class="text-3xl font-extrabold text-white">Guaranteed Correct Operations Center</h1>
                <p class="text-xs sm:text-sm text-slate-400">Manage dual AI engines (Gemini Previews + Claude Predictions) and tune parameters.</p>
            </div>

            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.pipeline.index') }}" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs border border-slate-700 transition-all flex items-center gap-1.5">
                    <span>⚡ Live Pipeline Logs</span>
                    @if(isset($lastRun) && $lastRun)
                        <span class="w-2 h-2 rounded-full @if($lastRun->status === 'running') bg-sky-400 animate-ping @elseif($lastRun->status === 'completed') bg-emerald-400 @elseif($lastRun->status === 'failed') bg-rose-400 @else bg-amber-400 @endif"></span>
                    @endif
                </a>
                <a href="{{ route('admin.settings') }}" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs border border-slate-700 transition-all">
                    ⚙️ AI Settings
                </a>
                <form action="{{ route('admin.pipeline.run') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-extrabold text-xs shadow-lg shadow-sky-500/25 transition-all flex items-center space-x-2">
                        <svg class="w-4 h-4 animate-spin-slow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/></svg>
                        <span>Run AI Pipeline Now</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="p-5 rounded-2xl glass-panel border border-slate-800">
                <div class="text-xs font-semibold text-slate-400 uppercase">Active Fixtures</div>
                <div class="text-3xl font-black text-white mt-1 font-mono">{{ $matchCount }}</div>
                <div class="text-[10px] text-slate-500 mt-1">Ingested in database</div>
            </div>

            <div class="p-5 rounded-2xl glass-panel border border-slate-800">
                <div class="text-xs font-semibold text-slate-400 uppercase">AI Predictions</div>
                <div class="text-3xl font-black text-[#38BDF8] mt-1 font-mono">{{ $predictionCount }}</div>
                <div class="text-[10px] text-slate-500 mt-1">Claude & xG calculated</div>
            </div>

            <div class="p-5 rounded-2xl glass-panel border border-slate-800">
                <div class="text-xs font-semibold text-slate-400 uppercase">PRO Subscribers</div>
                <div class="text-3xl font-black text-emerald-400 mt-1 font-mono">{{ $subscriberCount }}</div>
                <div class="text-[10px] text-slate-500 mt-1">Active paid tier accounts</div>
            </div>

            <div class="p-5 rounded-2xl glass-panel border border-slate-800">
                <div class="text-xs font-semibold text-slate-400 uppercase">Free Accounts</div>
                <div class="text-3xl font-black text-amber-400 mt-1 font-mono">{{ $freeUserCount }}</div>
                <div class="text-[10px] text-slate-500 mt-1">Ad-supported tier users</div>
            </div>
        </div>

        <!-- Dual AI Engine Status Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Gemini Match Previews Engine Card -->
            <div class="p-6 rounded-3xl bg-[#151A24] border-2 border-sky-500/30 space-y-4 relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <span class="p-2.5 rounded-xl bg-sky-500/10 text-sky-400 border border-sky-500/30 font-bold">
                            ♊
                        </span>
                        <div>
                            <h3 class="font-extrabold text-base text-white">Gemini Match Previews Engine</h3>
                            <p class="text-xs text-slate-400">Generates tactical narrative match editorials.</p>
                        </div>
                    </div>
                    <span class="px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 text-[10px] font-bold uppercase">
                        ACTIVE
                    </span>
                </div>

                <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800 text-xs space-y-2">
                    <div class="flex justify-between text-slate-400">
                        <span>Configured Model:</span>
                        <span class="font-bold text-slate-200 font-mono">{{ $settings['gemini_model'] }}</span>
                    </div>
                    <div class="flex justify-between text-slate-400">
                        <span>API Key Status:</span>
                        <span class="font-bold text-sky-400">{{ ($settings['gemini_api_key_configured'] ?? false) ? 'Custom Key Set' : 'Not Configured' }}</span>
                    </div>
                </div>
            </div>

            <!-- AI Predictions Engine Card -->
            <div class="p-6 rounded-3xl bg-[#151A24] border-2 border-indigo-500/30 space-y-4 relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <span class="p-2.5 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/30 font-bold">
                            🤖
                        </span>
                        <div>
                            @php
                                $providerLabels = [
                                    'claude' => 'Claude AI Engine',
                                    'chatgpt' => 'ChatGPT / OpenAI Engine',
                                    'openai' => 'ChatGPT / OpenAI Engine',
                                    'gemini' => 'Google Gemini Engine',
                                    'kimi' => 'Moonshot Kimi Engine',
                                    'poisson_xg' => 'Poisson xG Matrix',
                                ];
                                $activeLabel = $providerLabels[$settings['prediction_provider']] ?? strtoupper($settings['prediction_provider']);
                            @endphp
                            <h3 class="font-extrabold text-base text-white">{{ $activeLabel }}</h3>
                            <p class="text-xs text-slate-400">Calculates market probabilities & confidence scores.</p>
                        </div>
                    </div>
                    <span class="px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 text-[10px] font-bold uppercase">
                        ACTIVE
                    </span>
                </div>

                <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800 text-xs space-y-2">
                    <div class="flex justify-between text-slate-400">
                        <span>Engine Provider:</span>
                        <span class="font-bold text-indigo-400 uppercase">{{ $settings['prediction_provider'] }}</span>
                    </div>
                    <div class="flex justify-between text-slate-400">
                        <span>Min Confidence Floor:</span>
                        <span class="font-bold text-emerald-400 font-mono">{{ round(((float)$settings['min_confidence_threshold']) * 100, 1) }}%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Prediction Markets & Model Accuracy Registry -->
        <div class="p-6 sm:p-8 rounded-3xl glass-panel border border-slate-800 space-y-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-sky-500/10 text-[#38BDF8] border border-sky-500/30 text-xs font-bold mb-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        <span>8 CONFIGURED PREDICTION MARKETS (7 PUBLIC LISTS)</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-white">Prediction Market Registry & Accuracy</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Live status, promotion confidence thresholds, and audited model hit rates across all markets.</p>
                </div>
                <a href="{{ route('admin.predictions.index') }}" class="px-4 py-2 text-xs font-bold rounded-xl bg-sky-500 hover:bg-sky-400 text-slate-950 transition-all flex items-center gap-1.5 whitespace-nowrap">
                    <span>Manage All Picks &rarr;</span>
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @php
                    $marketColors = [
                        'win'              => ['badge' => 'bg-sky-500/10 text-sky-400 border-sky-500/30',         'rate' => 'text-sky-400'],
                        'over_2_5'         => ['badge' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30', 'rate' => 'text-emerald-400'],
                        'gg'               => ['badge' => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/30',   'rate' => 'text-indigo-400'],
                        'fh_over_0_5'      => ['badge' => 'bg-teal-500/10 text-teal-400 border-teal-500/30',       'rate' => 'text-teal-400'],
                        'ht_win'           => ['badge' => 'bg-violet-500/10 text-violet-400 border-violet-500/30',   'rate' => 'text-violet-400'],
                        'corners_over_8_5' => ['badge' => 'bg-orange-500/10 text-orange-400 border-orange-500/30',   'rate' => 'text-orange-400'],
                        'cards_over_2_5'   => ['badge' => 'bg-rose-500/10 text-rose-400 border-rose-500/30',       'rate' => 'text-rose-400'],
                        'win_draw_loss'    => ['badge' => 'bg-slate-800 text-slate-300 border-slate-700',           'rate' => 'text-slate-300'],
                    ];
                @endphp

                @foreach($markets as $key => $market)
                    @php
                        $color = $marketColors[$key] ?? ['badge' => 'bg-slate-800 text-slate-300 border-slate-700', 'rate' => 'text-slate-300'];
                        $threshold = \App\Support\MarketRegistry::threshold($key);
                        $marketStats = $stats['ai'][$key] ?? ['rate' => 0, 'won' => 0, 'total' => 0];
                    @endphp
                    <div class="p-4 rounded-2xl bg-slate-900/90 border border-slate-800 hover:border-slate-700 transition-all flex flex-col justify-between space-y-3 group">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase border {{ $color['badge'] }}">
                                    {{ $market->short }}
                                </span>
                                @if($market->listed)
                                    <span class="text-[10px] font-bold text-emerald-400">Public Top 10</span>
                                @else
                                    <span class="text-[10px] font-bold text-slate-500">Unlisted (1X2)</span>
                                @endif
                            </div>

                            <h3 class="font-extrabold text-sm text-white group-hover:text-[#38BDF8] transition-colors truncate">
                                {{ $market->label }}
                            </h3>
                            <div class="text-[11px] text-slate-500 mt-0.5">
                                Family: <code class="text-slate-400">{{ $market->family }}</code>
                                @if($market->line) · Line: <span class="text-white font-mono">{{ $market->line }}</span> @endif
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-800/80 space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-400">Model Hit Rate:</span>
                                <span class="font-mono font-black {{ $color['rate'] }}">
                                    {{ $marketStats['rate'] }}% <span class="text-[10px] font-normal text-slate-500">({{ $marketStats['won'] }}/{{ $marketStats['total'] }})</span>
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-400">Publish Floor:</span>
                                <span class="font-mono font-bold text-slate-300">
                                    {{ round($threshold * 100, 1) }}%
                                </span>
                            </div>

                            <a href="{{ route('admin.predictions.index', ['market' => $key]) }}" class="w-full mt-2 py-1.5 rounded-lg bg-slate-950 hover:bg-slate-800 text-sky-400 hover:text-white border border-slate-800 text-[11px] font-bold text-center block transition-colors">
                                View & Override {{ $market->short }} Picks &rarr;
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Quick Access Admin Modules --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <a href="{{ route('admin.pipeline.index') }}" class="p-5 rounded-2xl glass-panel border border-slate-800 hover:border-sky-500/50 transition-all group text-center">
                <div class="text-2xl mb-2">⚡</div>
                <div class="font-bold text-sm text-white group-hover:text-sky-400 transition-colors">Pipeline</div>
                <div class="text-[10px] text-slate-500 mt-1">Live Logs & Runner</div>
            </a>
            <a href="{{ route('admin.matches.index') }}" class="p-5 rounded-2xl glass-panel border border-slate-800 hover:border-sky-500/50 transition-all group text-center">
                <div class="text-2xl mb-2">⚽</div>
                <div class="font-bold text-sm text-white group-hover:text-sky-400 transition-colors">Matches</div>
                <div class="text-[10px] text-slate-500 mt-1">Fixtures & Settlements</div>
            </a>
            <a href="{{ route('admin.predictions.index') }}" class="p-5 rounded-2xl glass-panel border border-slate-800 hover:border-indigo-500/50 transition-all group text-center">
                <div class="text-2xl mb-2">🧠</div>
                <div class="font-bold text-sm text-white group-hover:text-indigo-400 transition-colors">Predictions</div>
                <div class="text-[10px] text-slate-500 mt-1">Override AI Picks</div>
            </a>
            <a href="{{ route('admin.users.index') }}" class="p-5 rounded-2xl glass-panel border border-slate-800 hover:border-emerald-500/50 transition-all group text-center">
                <div class="text-2xl mb-2">👤</div>
                <div class="font-bold text-sm text-white group-hover:text-emerald-400 transition-colors">Users</div>
                <div class="text-[10px] text-slate-500 mt-1">Roles & Subscriptions</div>
            </a>
            <a href="{{ route('admin.experts.index') }}" class="p-5 rounded-2xl glass-panel border border-slate-800 hover:border-amber-500/50 transition-all group text-center">
                <div class="text-2xl mb-2">🏆</div>
                <div class="font-bold text-sm text-white group-hover:text-amber-400 transition-colors">Experts</div>
                <div class="text-[10px] text-slate-500 mt-1">Profiles & Picks</div>
            </a>
            <a href="{{ route('admin.ads.index') }}" class="p-5 rounded-2xl glass-panel border border-slate-800 hover:border-blue-500/50 transition-all group text-center">
                <div class="text-2xl mb-2">📢</div>
                <div class="font-bold text-sm text-white group-hover:text-blue-400 transition-colors">Ads</div>
                <div class="text-[10px] text-slate-500 mt-1">Sponsor & Banners</div>
            </a>
        </div>
    </div>
@endsection
