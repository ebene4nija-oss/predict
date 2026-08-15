@extends('layouts.app')

@section('title', 'Ad Banner & Sponsorship Control — Admin Portal')

@section('content')
    <div class="max-w-4xl mx-auto space-y-8 py-4">
        <!-- Admin Navigation Bar -->
        <div class="flex items-center space-x-1 p-1 rounded-2xl glass-panel mb-6 overflow-x-auto">
            <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Dashboard</a>
            <a href="{{ route('admin.matches.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.matches.*') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Matches</a>
            <a href="{{ route('admin.predictions.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.predictions.*') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Predictions</a>
            <a href="{{ route('admin.users.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.users.*') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Users</a>
            <a href="{{ route('admin.experts.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.experts.*') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Experts</a>
            <a href="{{ route('admin.ads.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.ads.*') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Ads</a>
            <a href="{{ route('admin.settings') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.settings*') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Settings</a>
        </div>

        <!-- Flash Message Banner -->
        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-semibold flex items-center space-x-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Page Header -->
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white">Ad Banner & Sponsorship Control</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">Configure global betting partner ad banners displayed to free-tier users across Guaranteed Correct.</p>
        </div>

        <!-- Sponsor Settings Form -->
        <div class="p-6 sm:p-8 rounded-3xl glass-panel border border-slate-800 space-y-6">
            <form action="{{ route('admin.ads.update') }}" method="POST" class="space-y-5">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Ad Banner Status</label>
                        <select name="ad_enabled" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs font-semibold focus:border-sky-400 focus:outline-none">
                            <option value="1" {{ old('ad_enabled', $ads['ad_enabled']) == '1' ? 'selected' : '' }}>Enabled (Visible to Free Users)</option>
                            <option value="0" {{ old('ad_enabled', $ads['ad_enabled']) == '0' ? 'selected' : '' }}>Disabled (Hidden)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Ad Source</label>
                        <select name="ad_mode" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs font-semibold focus:border-sky-400 focus:outline-none">
                            <option value="house" {{ old('ad_mode', $ads['ad_mode']) === 'house' ? 'selected' : '' }}>House Banner (earns nothing)</option>
                            <option value="network" {{ old('ad_mode', $ads['ad_mode']) === 'network' ? 'selected' : '' }}>Ad Network Tags (live revenue)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Sponsor / Partner Name</label>
                        <input type="text" name="ad_partner_name" value="{{ old('ad_partner_name', $ads['ad_partner_name']) }}" required placeholder="e.g. Official Betting Partner" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs focus:border-sky-400 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Headline / Promotion Title</label>
                    <input type="text" name="ad_headline" value="{{ old('ad_headline', $ads['ad_headline']) }}" required placeholder="e.g. High Odds Multiples & 200% Deposit Bonus" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs focus:border-sky-400 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Ad Description Copy</label>
                    <textarea name="ad_description" rows="3" required placeholder="Description copy shown to user..." class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs focus:border-sky-400 focus:outline-none">{{ old('ad_description', $ads['ad_description']) }}</textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-2">CTA Button Text</label>
                        <input type="text" name="ad_cta_text" value="{{ old('ad_cta_text', $ads['ad_cta_text']) }}" required placeholder="e.g. Claim Bonus" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs focus:border-sky-400 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-2">CTA Target URL</label>
                        <input type="text" name="ad_cta_url" value="{{ old('ad_cta_url', $ads['ad_cta_url']) }}" required placeholder="https://..." class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs font-mono focus:border-sky-400 focus:outline-none">
                    </div>
                </div>

                <!-- Ad Network Tags -->
                <div class="pt-4 border-t border-slate-800 space-y-5">
                    <div>
                        <h3 class="text-sm font-extrabold text-white">Ad Network Tags</h3>
                        <p class="text-[11px] text-slate-500 mt-1">
                            Paste the tags from your ad network (Google Ad Manager, AdSense, or similar).
                            These are inserted into the page exactly as given, so paste only code you got
                            from the network itself. They render for free-tier visitors only &mdash;
                            subscribers load no ad script at all. Used when <strong>Ad Source</strong> is
                            set to Ad Network Tags.
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Loader Script (goes in &lt;head&gt;)</label>
                        <textarea name="ad_network_head" rows="3" placeholder="&lt;script async src=&quot;...&quot;&gt;&lt;/script&gt;" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-[11px] font-mono focus:border-sky-400 focus:outline-none">{{ old('ad_network_head', $ads['ad_network_head']) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Default Unit</label>
                        <p class="text-[11px] text-slate-500 mb-2">Used for any placement left blank below &mdash; paste a responsive unit here and you can leave the rest empty.</p>
                        <textarea name="ad_network_default" rows="3" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-[11px] font-mono focus:border-sky-400 focus:outline-none">{{ old('ad_network_default', $ads['ad_network_default']) }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        @foreach ([
                            'ad_network_header' => 'Header Placement',
                            'ad_network_in_content' => 'In-Content Placement',
                            'ad_network_sidebar' => 'Sidebar Placement',
                            'ad_network_footer' => 'Footer Placement',
                        ] as $field => $label)
                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-2">{{ $label }}</label>
                                <textarea name="{{ $field }}" rows="3" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-[11px] font-mono focus:border-sky-400 focus:outline-none">{{ old($field, $ads[$field]) }}</textarea>
                            </div>
                        @endforeach
                    </div>

                    <div class="p-3 rounded-xl bg-amber-500/5 border border-amber-500/20 text-[11px] text-amber-300/90">
                        Gambling and betting ads are restricted in several of the markets this site serves,
                        and carrying them cuts against the trust pages. Decide deliberately whether to allow
                        that category in your network's settings &mdash; it is not a default worth accepting.
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-sky-500 to-indigo-600 hover:from-sky-400 hover:to-indigo-500 text-white font-extrabold text-xs shadow-lg shadow-sky-500/25 transition-all">
                        Save Sponsor & Ad Settings
                    </button>
                </div>
            </form>
        </div>

        <!-- Live Preview Section -->
        <div class="space-y-3 pt-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-extrabold text-white flex items-center space-x-2">
                    <span>📢 Live Ad Banner Preview</span>
                </h3>
                @if($ads['ad_enabled'] == '1')
                    <span class="px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 text-[10px] font-extrabold uppercase">
                        Active Banner
                    </span>
                @else
                    <span class="px-2.5 py-1 rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/30 text-[10px] font-extrabold uppercase">
                        Banner Disabled
                    </span>
                @endif
            </div>

            <div class="p-6 rounded-3xl glass-panel border border-slate-800 relative">
                @if($ads['ad_enabled'] == '0')
                    <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-[2px] rounded-3xl flex items-center justify-center z-10">
                        <div class="px-4 py-2 rounded-xl bg-slate-900 border border-slate-700 text-slate-400 text-xs font-bold">
                            Ad banner is currently disabled in settings
                        </div>
                    </div>
                @endif

                <div class="p-4 rounded-xl bg-[#151A24]/60 border border-slate-800/80 text-center transition-all">
                    <div class="text-[10px] tracking-widest uppercase text-slate-500 font-semibold mb-1">Sponsored Advertisement</div>
                    <div class="py-3 px-4 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 rounded-lg border border-slate-700/50 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <div class="flex items-center space-x-3 text-left">
                            <span class="p-2 bg-blue-500/10 text-blue-400 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </span>
                            <div>
                                <div class="text-sm font-semibold text-slate-200">{{ $ads['ad_partner_name'] }} • {{ $ads['ad_headline'] }}</div>
                                <div class="text-xs text-slate-400">{{ $ads['ad_description'] }}</div>
                            </div>
                        </div>
                        <a href="{{ $ads['ad_cta_url'] }}" target="_blank" rel="noopener noreferrer" class="px-4 py-1.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-500 rounded-lg transition-colors whitespace-nowrap">
                            {{ $ads['ad_cta_text'] }}
                        </a>
                    </div>
                    <div class="mt-1 text-[11px] text-slate-500">
                        Tired of ads? <a href="{{ route('subscription.pricing') }}" class="text-[#38BDF8] underline">Upgrade to PRO</a> for an ad-free experience.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
