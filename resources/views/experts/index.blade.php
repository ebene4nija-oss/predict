@extends('layouts.app')

@section('title', 'Expert Picks — Human Analyst Insights')

@section('content')
    <x-ad-banner type="header" />

    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-[#F5A623]/10 text-[#F5A623] border border-[#F5A623]/30 text-xs font-bold mb-2">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span>VERIFIED HUMAN EXPERTS</span>
                </div>
                <h1 class="text-3xl font-extrabold text-white">Human Expert Picks</h1>
                <p class="text-xs sm:text-sm text-slate-400">Independent tactical analysts publishing alongside the AI pipeline with clear source attribution.</p>
            </div>

            @auth
                @if(auth()->user()->isExpert())
                    <a href="{{ route('expert.submit') }}" class="px-4 py-2.5 rounded-xl bg-[#F5A623] text-slate-950 font-bold text-xs hover:bg-amber-400 transition-all">
                        + Submit Expert Pick
                    </a>
                @endif
            @endauth
        </div>

        <div class="space-y-4">
            @foreach($expertPicks as $index => $pick)
                @php
                    $isUnlocked = $isSubscriber || ($index < 2);
                @endphp

                @if($isUnlocked)
                    <div class="p-6 rounded-2xl glass-panel border border-slate-800 hover:border-amber-500/40 transition-all space-y-4">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-4 border-b border-slate-800">
                            <!-- Expert Byline -->
                            <div class="flex items-center space-x-3">
                                <img src="{{ $pick->expert->photo_path ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=150' }}" class="w-11 h-11 rounded-full border-2 border-[#F5A623] object-cover" alt="Expert">
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <span class="font-extrabold text-sm text-white">{{ $pick->expert->name }}</span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#F5A623]/10 text-[#F5A623] border border-[#F5A623]/30">EXPERT</span>
                                    </div>
                                    <div class="text-xs text-slate-400 truncate max-w-sm">{{ $pick->expert->bio }}</div>
                                </div>
                            </div>

                            <!-- Fixture Info -->
                            <div class="text-left sm:text-right">
                                <div class="text-xs text-slate-400">{{ $pick->match->league }}</div>
                                <a href="{{ route('matches.show', $pick->match) }}" class="font-extrabold text-sm text-white hover:text-sky-400">
                                    {{ $pick->match->home_team }} vs {{ $pick->match->away_team }}
                                </a>
                            </div>
                        </div>

                        <!-- Pick Details -->
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <div class="space-y-1">
                                <div class="text-xs text-slate-400 font-medium">
                                    Market: <span class="text-amber-400 font-bold">{{ strtoupper(str_replace('_', ' ', $pick->market)) }}</span>
                                </div>
                                <div class="text-lg font-black text-white">
                                    Recommendation: <span class="text-amber-400">{{ $pick->pick }}</span>
                                </div>
                                <p class="text-xs text-slate-300 italic pt-1 max-w-2xl">"{{ $pick->rationale }}"</p>
                            </div>

                            <div class="text-right bg-slate-900/80 px-4 py-2 rounded-xl border border-slate-800">
                                <div class="text-[10px] text-slate-500 uppercase font-semibold">Analyst Confidence</div>
                                <div class="text-base font-extrabold text-amber-400 font-mono">{{ round($pick->confidence * 100, 1) }}%</div>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Locked Expert Pick Card -->
                    <div class="relative p-6 rounded-2xl glass-panel border border-amber-500/20 overflow-hidden">
                        <div class="filter blur-md select-none pointer-events-none opacity-30 flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-full bg-slate-800"></div>
                                <div>
                                    <div class="font-bold text-white">Expert Analyst Pick Locked</div>
                                    <div class="text-xs text-slate-400">Tactical analysis hidden for free tier</div>
                                </div>
                            </div>
                        </div>

                        <div class="absolute inset-0 bg-[#0B0F17]/85 backdrop-blur-sm flex items-center justify-between px-6 py-4">
                            <div class="flex items-center space-x-3">
                                <span class="p-2 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/30">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                </span>
                                <div>
                                    <div class="text-xs font-bold text-white">Expert Pick #{{ $index + 1 }} Gated</div>
                                    <div class="text-[11px] text-slate-400">Subscribe to view human expert tactical breakdowns.</div>
                                </div>
                            </div>
                            <a href="{{ route('subscription.pricing') }}" class="px-4 py-2 rounded-xl text-xs font-extrabold bg-[#F5A623] hover:bg-amber-400 text-slate-950 transition-all whitespace-nowrap">
                                Unlock Expert Picks
                            </a>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
@endsection
