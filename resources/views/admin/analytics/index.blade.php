@extends('layouts.app')

@section('title', 'Comprehensive Platform Analytics — Admin Control Portal')

@section('content')
    <div class="max-w-6xl mx-auto space-y-8 py-4">
        {{-- Admin Navigation Bar --}}
        <div class="flex items-center space-x-1 p-1 rounded-2xl glass-panel overflow-x-auto">
            <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Dashboard</a>
            <a href="{{ route('admin.analytics.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.analytics.*') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Analytics</a>
            <a href="{{ route('admin.matches.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.matches.*') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Matches</a>
            <a href="{{ route('admin.predictions.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.predictions.*') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Predictions</a>
            <a href="{{ route('admin.users.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.users.*') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Users</a>
            <a href="{{ route('admin.experts.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.experts.*') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Experts</a>
            <a href="{{ route('admin.ads.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.ads.*') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Ads</a>
            <a href="{{ route('admin.settings') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.settings*') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Settings</a>
        </div>

        <!-- Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 text-xs font-bold mb-2">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <span>EXECUTIVE INTERNAL ANALYTICS DASHBOARD</span>
                </div>
                <h1 class="text-3xl font-extrabold text-white">Platform Performance & Traffic Metrics</h1>
                <p class="text-xs sm:text-sm text-slate-400">Real-time telemetry across visitors, conversion funnels, Telegram bot activity, and SEO pageviews.</p>
            </div>
        </div>

        <!-- Top Metric Stat Strip -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="p-5 rounded-2xl glass-panel border border-slate-800">
                <div class="text-xs font-semibold text-slate-400 uppercase">Today's Pageviews</div>
                <div class="text-3xl font-black text-white mt-1 font-mono">{{ number_format($todayPageviews) }}</div>
                <div class="text-[10px] text-slate-500 mt-1">Total lifetime: {{ number_format($totalPageviews) }}</div>
            </div>

            <div class="p-5 rounded-2xl glass-panel border border-slate-800">
                <div class="text-xs font-semibold text-slate-400 uppercase">Unique Visitors Today</div>
                <div class="text-3xl font-black text-[#38BDF8] mt-1 font-mono">{{ number_format($uniqueVisitorsToday) }}</div>
                <div class="text-[10px] text-slate-500 mt-1">Distinct IP addresses</div>
            </div>

            <div class="p-5 rounded-2xl glass-panel border border-slate-800">
                <div class="text-xs font-semibold text-slate-400 uppercase">PRO Conversion Rate</div>
                <div class="text-3xl font-black text-emerald-400 mt-1 font-mono">{{ $conversionRate }}%</div>
                <div class="text-[10px] text-slate-500 mt-1">{{ $subscribersCount }} paid of {{ $totalUsers }} users</div>
            </div>

            <div class="p-5 rounded-2xl glass-panel border border-slate-800">
                <div class="text-xs font-semibold text-slate-400 uppercase">Est. Monthly MRR</div>
                <div class="text-3xl font-black text-amber-400 mt-1 font-mono">${{ number_format($monthlyRevenueEstimate, 2) }}</div>
                <div class="text-[10px] text-slate-500 mt-1">Based on $9.99/mo PRO tier</div>
            </div>
        </div>

        <!-- Breakdown Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Telegram & Conversion Card -->
            <div class="p-6 rounded-3xl bg-[#151A24] border border-sky-500/30 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-extrabold text-base text-white flex items-center space-x-2">
                        <span class="text-sky-400">✈️</span>
                        <span>Telegram Community Telemetry</span>
                    </h3>
                    <span class="px-2.5 py-0.5 rounded-full bg-sky-500/10 text-sky-400 border border-sky-500/30 text-[10px] font-bold uppercase">BOT METRICS</span>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800">
                        <div class="text-xs text-slate-400">Connected Accounts</div>
                        <div class="text-2xl font-black text-white font-mono mt-1">{{ $telegramConnectedUsers }}</div>
                        <div class="text-[10px] text-slate-500 mt-1">Users linked via /start</div>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800">
                        <div class="text-xs text-slate-400">Active DM Subscribers</div>
                        <div class="text-2xl font-black text-emerald-400 font-mono mt-1">{{ $telegramActiveSubscribers }}</div>
                        <div class="text-[10px] text-slate-500 mt-1">Daily picks opted-in</div>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800 text-xs space-y-2 text-slate-300">
                    <div class="flex justify-between">
                        <span>VIP Public Channel:</span>
                        <span class="font-bold text-sky-400">@ProphetAIPicks</span>
                    </div>
                    <div class="flex justify-between">
                        <span>High Conviction Picks (75%+):</span>
                        <span class="font-bold text-amber-400 font-mono">{{ $highConvictionPicks }} picks</span>
                    </div>
                </div>
            </div>

            <!-- Top Visited Pages -->
            <div class="p-6 rounded-3xl glass-panel border border-slate-800 space-y-4">
                <h3 class="font-extrabold text-base text-white flex items-center space-x-2">
                    <span class="text-indigo-400">🔥</span>
                    <span>Most Visited Site Routes</span>
                </h3>

                <div class="space-y-2">
                    @forelse($topPages as $page)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-900/80 border border-slate-800 text-xs">
                            <span class="font-mono text-slate-200">{{ $page->path }}</span>
                            <span class="px-2.5 py-1 rounded-lg bg-indigo-500/10 text-indigo-400 font-bold font-mono border border-indigo-500/20">
                                {{ number_format($page->views) }} views
                            </span>
                        </div>
                    @empty
                        <div class="text-center py-6 text-slate-500 text-xs">No pageviews recorded yet. Browse around the site to generate telemetry.</div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- Recent Realtime Visitor Log -->
        <div class="p-6 rounded-3xl glass-panel border border-slate-800 space-y-4">
            <h3 class="font-extrabold text-base text-white">Realtime Visitor Stream (Last 15 Pageviews)</h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="uppercase bg-slate-900/80 text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="py-3 px-4">Time</th>
                            <th class="py-3 px-4">Path</th>
                            <th class="py-3 px-4">User</th>
                            <th class="py-3 px-4">IP Address</th>
                            <th class="py-3 px-4">User Agent</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-mono">
                        @forelse($recentPageviews as $view)
                            <tr class="hover:bg-slate-900/40">
                                <td class="py-3 px-4 text-slate-500">{{ $view->created_at->diffForHumans() }}</td>
                                <td class="py-3 px-4 text-sky-400 font-bold">{{ $view->path }}</td>
                                <td class="py-3 px-4 text-slate-300">
                                    {{ $view->user ? $view->user->name . ' (' . $view->user->role . ')' : 'Guest' }}
                                </td>
                                <td class="py-3 px-4 text-slate-400">{{ $view->ip_address ?? '127.0.0.1' }}</td>
                                <td class="py-3 px-4 text-slate-500 truncate max-w-xs">{{ mb_substr($view->user_agent, 0, 40) }}...</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-slate-500">No pageview logs.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
