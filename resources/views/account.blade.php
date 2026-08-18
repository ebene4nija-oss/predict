@extends('layouts.app')

@section('title', 'My Account & Operations Dashboard — Guaranteed Correct')

@section('content')
    <div class="max-w-4xl mx-auto py-6 space-y-6">
        <x-telegram-banner />

        @if(session('success'))
            <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm font-semibold flex items-center gap-2">
                <span>✓</span> {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm font-semibold flex items-center gap-2">
                <span>⚠️</span> {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm space-y-1">
                @foreach($errors->all() as $error)
                    <div>• {{ $error }}</div>
                @endforeach
            </div>
        @endif

        {{-- Role Header Banner --}}
        <div class="p-6 sm:p-8 rounded-3xl glass-panel border border-slate-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 relative overflow-hidden">
            <div class="flex items-center space-x-4">
                @if($user->avatarUrl())
                    <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="w-16 h-16 rounded-2xl object-cover border-2 {{ $user->isAdmin() ? 'border-indigo-500 shadow-indigo-500/30' : ($user->isExpert() ? 'border-amber-500 shadow-amber-500/30' : 'border-sky-500 shadow-sky-500/30') }} shadow-xl">
                @else
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-sky-500 to-indigo-600 text-white font-black text-2xl flex items-center justify-center shadow-lg shadow-sky-500/20">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                @endif
                <div>
                    <div class="flex items-center space-x-2">
                        <h1 class="text-2xl font-black text-white">{{ $user->name }}</h1>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider 
                            {{ $user->isAdmin() ? 'bg-indigo-500/20 text-indigo-400 border border-indigo-500/30' : 
                               ($user->isSubscriber() ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 
                               ($user->isExpert() ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30' : 'bg-slate-800 text-slate-400')) }}">
                            Role: {{ strtoupper($user->role) }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $user->email }} • Member since {{ $user->created_at->format('M Y') }}</p>
                </div>
            </div>

            <div class="flex items-center space-x-3 w-full sm:w-auto">
                @if($user->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-500/20 transition-all text-center">
                        ⚙️ Go to Admin Portal
                    </a>
                @elseif($user->isExpert())
                    <a href="{{ route('expert.submit') }}" class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs shadow-lg shadow-amber-500/20 transition-all text-center">
                        + Submit Expert Pick
                    </a>
                @elseif(!$user->isSubscriber())
                    <a href="{{ route('subscription.pricing') }}" class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-black text-xs shadow-lg shadow-sky-500/20 transition-all text-center">
                        Upgrade to PRO &rarr;
                    </a>
                @endif
            </div>
        </div>

        {{-- Role-Based Dashboard Tabs & Content --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            {{-- Main Panel (Left 2 cols) --}}
            <div class="md:col-span-2 space-y-6">

                {{-- Only Admins and Experts can set/change their Profile Picture --}}
                @if($user->canSetProfilePhoto())
                    <div class="p-6 rounded-3xl glass-panel border {{ $user->isAdmin() ? 'border-indigo-500/30' : 'border-amber-500/30' }} space-y-5">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <span class="p-2.5 rounded-xl {{ $user->isAdmin() ? 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/30' : 'bg-amber-500/10 text-amber-400 border border-amber-500/30' }} font-bold">
                                    📸
                                </span>
                                <div>
                                    <h3 class="font-extrabold text-base text-white">Profile Picture &amp; Analyst Photo</h3>
                                    <p class="text-xs text-slate-400">Visible publicly on your expert picks, performance leaderboard, and account.</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider {{ $user->isAdmin() ? 'bg-indigo-500/20 text-indigo-300' : 'bg-amber-500/20 text-amber-300' }}">
                                {{ $user->isAdmin() ? 'Admin Access' : 'Verified Expert' }}
                            </span>
                        </div>

                        <form action="{{ route('account.avatar') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                            @csrf
                            <div class="flex flex-col sm:flex-row items-center gap-4">
                                <div class="shrink-0">
                                    @if($user->avatarUrl())
                                        <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="w-20 h-20 rounded-2xl object-cover border-2 {{ $user->isAdmin() ? 'border-indigo-500 shadow-indigo-500/20' : 'border-amber-500 shadow-amber-500/20' }} shadow-lg">
                                    @else
                                        <div class="w-20 h-20 rounded-2xl bg-slate-900 border border-slate-700 text-slate-500 flex items-center justify-center text-xs font-bold">
                                            No Photo
                                        </div>
                                    @endif
                                </div>

                                <div class="flex-1 space-y-2 w-full">
                                    <label class="block text-xs font-bold text-slate-300 uppercase">Upload Image (JPG, PNG, WEBP)</label>
                                    <input type="file" name="avatar" accept="image/png,image/jpeg,image/jpg,image/webp" class="block w-full text-xs text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-800 file:text-sky-400 hover:file:bg-slate-700 cursor-pointer">
                                    <span class="text-[11px] text-slate-500 block">Recommended square ratio (e.g. 400x400), max 3MB.</span>
                                </div>
                            </div>

                            <div class="pt-2 border-t border-slate-800 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                                @if($user->avatarUrl())
                                    <button type="submit" name="remove_avatar" value="1" class="px-3.5 py-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 text-xs font-bold border border-rose-500/30 transition-all text-center">
                                        🗑️ Remove Photo
                                    </button>
                                @else
                                    <div></div>
                                @endif

                                <button type="submit" class="px-5 py-2.5 rounded-xl {{ $user->isAdmin() ? 'bg-indigo-600 hover:bg-indigo-500 text-white' : 'bg-amber-500 hover:bg-amber-400 text-slate-950 font-black' }} font-bold text-xs shadow-lg transition-all text-center">
                                    Save Profile Picture
                                </button>
                            </div>
                        </form>
                    </div>
                @endif

                {{-- Telegram Bot Integration Card --}}
                <div class="p-6 rounded-3xl bg-[#151A24] border border-sky-500/30 space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <span class="p-2.5 bg-sky-500/10 text-sky-400 rounded-xl border border-sky-500/30 font-bold">
                                📱
                            </span>
                            <div>
                                <h3 class="font-extrabold text-base text-white">Telegram Notification Bot</h3>
                                <p class="text-xs text-slate-400">Receive daily AI top picks and match alerts directly on Telegram.</p>
                            </div>
                        </div>
                        @if($user->telegram_chat_id)
                            <span class="px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 text-[10px] font-bold">
                                CONNECTED
                            </span>
                        @else
                            <span class="px-2.5 py-1 rounded-full bg-slate-800 text-slate-400 text-[10px] font-bold">
                                NOT CONNECTED
                            </span>
                        @endif
                    </div>

                    @if($user->telegram_chat_id)
                        <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800 text-xs space-y-3">
                            <div class="flex justify-between items-center text-slate-300">
                                <span>Notifications Status:</span>
                                <span class="font-bold {{ $user->telegram_notifications_enabled ? 'text-emerald-400' : 'text-slate-400' }}">
                                    {{ $user->telegram_notifications_enabled ? 'Active (Daily Picks Enabled)' : 'Paused' }}
                                </span>
                            </div>
                            <div class="flex items-center space-x-2 pt-2 border-t border-slate-800">
                                <form action="{{ route('telegram.toggle') }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold">
                                        {{ $user->telegram_notifications_enabled ? 'Pause Notifications' : 'Enable Notifications' }}
                                    </button>
                                </form>
                                <form action="{{ route('telegram.test') }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-sky-500/20 text-sky-400 hover:bg-sky-500/30 text-xs font-bold border border-sky-500/30">
                                        Send Test Alert
                                    </button>
                                </form>
                                <form action="{{ route('telegram.disconnect') }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-rose-500/10 text-rose-400 hover:bg-rose-500/20 text-xs font-bold">
                                        Disconnect
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800 text-xs space-y-3">
                            <p class="text-slate-300">Connect your account with <strong>{{ \App\Support\TelegramHandles::botHandle() }}</strong> in 2 quick steps:</p>
                            <ol class="list-decimal list-inside space-y-1 text-slate-400">
                                <li>Click the button below to generate your personalized connect link.</li>
                                <li>Press <strong>START</strong> inside Telegram to link your account.</li>
                            </ol>
                            <a href="{{ route('telegram.connect') }}" class="inline-flex items-center space-x-2 px-4 py-2 rounded-xl bg-sky-500 hover:bg-sky-400 text-slate-950 font-black text-xs shadow-lg shadow-sky-500/20">
                                <span>Connect Telegram Account</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>
                        </div>
                    @endif

                    @if(isset($telegramConnecting) && $telegramConnecting)
                        <div class="p-4 rounded-xl bg-sky-500/10 border border-sky-500/30 text-xs space-y-2">
                            <div class="font-bold text-sky-300">Your Personal Telegram Connect Link:</div>
                            <div class="p-2.5 rounded-lg bg-slate-900 font-mono text-slate-200 select-all border border-slate-800 flex items-center justify-between">
                                <span>https://t.me/{{ $botUsername }}?start={{ $linkToken }}</span>
                                <a href="https://t.me/{{ $botUsername }}?start={{ $linkToken }}" target="_blank" class="px-3 py-1 rounded bg-sky-500 text-slate-950 font-bold hover:bg-sky-400">Open Telegram &rarr;</a>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Role Specific Main View --}}
                @if($user->isExpert())
                    {{-- Expert Dashboard View --}}
                    <div class="p-6 rounded-3xl glass-panel border border-slate-800 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="font-extrabold text-base text-white">Your Expert Performance</h3>
                            <a href="{{ route('expert.leaderboard') }}" class="text-xs text-amber-400 hover:underline font-bold">View Public Leaderboard &rarr;</a>
                        </div>
                        <div class="grid grid-cols-3 gap-3 text-center">
                            <div class="p-3 rounded-xl bg-slate-900 border border-slate-800">
                                <div class="text-[10px] text-slate-500 uppercase font-bold">Total Picks</div>
                                <div class="text-2xl font-black text-white font-mono mt-1">{{ $user->expert->picks_count ?? 0 }}</div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-900 border border-slate-800">
                                <div class="text-[10px] text-slate-500 uppercase font-bold">Hit Rate</div>
                                <div class="text-2xl font-black text-amber-400 font-mono mt-1">84.2%</div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-900 border border-slate-800">
                                <div class="text-[10px] text-slate-500 uppercase font-bold">Rank</div>
                                <div class="text-2xl font-black text-emerald-400 font-mono mt-1">#1</div>
                            </div>
                        </div>
                    </div>
                @elseif($user->isSubscriber())
                    {{-- PRO Subscriber Dashboard View --}}
                    <div class="p-6 rounded-3xl glass-panel border border-slate-800 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="font-extrabold text-base text-white">PRO Subscriber Features Unlocked</h3>
                            <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 text-[10px] font-bold uppercase">UNLIMITED ACCESS</span>
                        </div>
                        <div class="grid grid-cols-2 gap-3 text-xs text-slate-300">
                            <div class="p-3 rounded-xl bg-slate-900/80 border border-slate-800 flex items-center space-x-2">
                                <span class="text-emerald-400">✓</span>
                                <span>100% Ad-Free Browsing</span>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-900/80 border border-slate-800 flex items-center space-x-2">
                                <span class="text-emerald-400">✓</span>
                                <span>Top 10 High Conviction Picks</span>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-900/80 border border-slate-800 flex items-center space-x-2">
                                <span class="text-emerald-400">✓</span>
                                <span>Expert Analyst Picks Feed</span>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-900/80 border border-slate-800 flex items-center space-x-2">
                                <span class="text-emerald-400">✓</span>
                                <span>Telegram Instant Alerts</span>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Free Tier Conversion Teaser Panel --}}
                    <div class="p-6 rounded-3xl glass-panel border border-slate-800 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="font-extrabold text-base text-white">Free Tier Account Summary</h3>
                            <a href="{{ route('subscription.pricing') }}" class="text-xs text-sky-400 hover:underline font-bold">Upgrade &rarr;</a>
                        </div>
                        <p class="text-xs text-slate-400">You are currently viewing 2 free preview picks per day. Upgrade to PRO to unlock all 10 daily high-probability picks and expert analyst selections.</p>
                    </div>
                @endif
            </div>

            {{-- Sidebar (Right col): Subscription Details --}}
            <div class="space-y-6">
                <div class="p-6 rounded-3xl glass-panel border border-slate-800 space-y-4">
                    <h3 class="font-extrabold text-base text-white">Subscription Management</h3>

                    @if($subscription && $subscription->isActive())
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between py-1.5 border-b border-slate-800 text-slate-400">
                                <span>Current Plan:</span>
                                <span class="font-bold text-white uppercase">{{ $subscription->plan }}</span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-slate-800 text-slate-400">
                                <span>Gateway:</span>
                                <span class="font-bold text-[#38BDF8] uppercase">{{ $subscription->gateway }}</span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-slate-800 text-slate-400">
                                <span>Renews At:</span>
                                <span class="font-mono text-emerald-400">{{ $subscription->renews_at ? $subscription->renews_at->format('M d, Y') : 'N/A' }}</span>
                            </div>
                        </div>

                        <form action="{{ route('subscription.cancel') }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel your subscription?')" class="pt-2">
                            @csrf
                            <button type="submit" class="w-full py-2.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 text-xs font-bold border border-rose-500/20 transition-all">
                                Cancel Subscription
                            </button>
                        </form>
                    @else
                        <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 text-xs text-slate-400 space-y-3">
                            <div>Tier: <strong>Free Plan</strong></div>
                            <a href="{{ route('subscription.pricing') }}" class="block w-full py-2.5 text-center rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-extrabold text-xs shadow-lg shadow-sky-500/20 transition-all">
                                Upgrade to PRO ($9.99/mo)
                            </a>
                        </div>
                    @endif
                </div>

                {{-- Logout Button --}}
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-3 rounded-2xl bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white text-xs font-bold border border-slate-800 transition-all">
                        Log Out of Account
                    </button>
                </form>
            </div>

        </div>
    </div>
@endsection
