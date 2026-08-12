@extends('layouts.app')

@section('title', 'AI Engine Parameter Control Panel — Admin Portal')

@section('content')
    <div class="max-w-3xl mx-auto py-6 space-y-6">
        {{-- Admin Navigation Bar --}}
        <div class="flex items-center space-x-1 p-1 rounded-2xl glass-panel overflow-x-auto">
            <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Dashboard</a>
            <a href="{{ route('admin.matches.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.matches.*') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Matches</a>
            <a href="{{ route('admin.predictions.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.predictions.*') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Predictions</a>
            <a href="{{ route('admin.users.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.users.*') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Users</a>
            <a href="{{ route('admin.experts.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.experts.*') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Experts</a>
            <a href="{{ route('admin.ads.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.ads.*') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Ads</a>
            <a href="{{ route('admin.settings') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.settings*') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Settings</a>
        </div>

        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm font-semibold">{{ session('success') }}</div>
        @endif

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('admin.dashboard') }}" class="text-xs font-bold text-slate-400 hover:text-sky-400">&larr; Back to Admin Dashboard</a>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white mt-1">AI Engine Parameter Settings</h1>
                <p class="text-xs text-slate-400">Configure Gemini API key for match previews and Claude parameters for predictions.</p>
            </div>
        </div>

        <div class="p-6 sm:p-8 rounded-3xl glass-panel border border-slate-800 space-y-6">
            <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-6">
                @csrf

                <!-- Section 1: API Credentials -->
                <div class="space-y-4 pb-6 border-b border-slate-800">
                    <h3 class="text-sm font-extrabold text-white uppercase tracking-wider text-sky-400">1. AI API Credentials</h3>
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Gemini API Key (Match Narrative Previews)</label>
                        <input type="text" name="gemini_api_key" value="{{ old('gemini_api_key', $settings['gemini_api_key']) }}" placeholder="AIzaSy..." class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-sky-400 focus:outline-none">
                        <span class="text-[11px] text-slate-500 mt-1 block">Used by <code>PreviewGenerationService</code> to author 2-paragraph editorial previews.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Claude API Key / Anthropic Key (AI Predictions)</label>
                        <input type="text" name="claude_api_key" value="{{ old('claude_api_key', $settings['claude_api_key']) }}" placeholder="sk-ant-api..." class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-sky-400 focus:outline-none">
                        <span class="text-[11px] text-slate-500 mt-1 block">Used by <code>ClaudePredictionService</code> to compute market probabilities & picks.</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-slate-800">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Google Analytics 4 (GA4 ID)</label>
                            <input type="text" name="ga4_measurement_id" value="{{ old('ga4_measurement_id', \App\Models\Setting::get('ga4_measurement_id', '')) }}" placeholder="G-XXXXXXXXXX" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-sky-400 focus:outline-none">
                            <span class="text-[11px] text-slate-500 mt-1 block">GA4 Measurement ID to enable gtag.js tracking.</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Google Search Console Meta Code</label>
                            <input type="text" name="search_console_verification_code" value="{{ old('search_console_verification_code', \App\Models\Setting::get('search_console_verification_code', '')) }}" placeholder="abc123VerificationCode..." class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-sky-400 focus:outline-none">
                            <span class="text-[11px] text-slate-500 mt-1 block">Verification code for google-site-verification meta tag.</span>
                        </div>
                    </div>

                    <!-- Branding, Logo & Favicon Management Section -->
                    <div class="pt-6 border-t border-slate-800 space-y-4">
                        <h3 class="text-sm font-extrabold text-white uppercase tracking-wider text-amber-400">🎨 Site Branding, Logos & Icons</h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Site Logo (URL or Asset Path)</label>
                                <input type="text" name="site_logo" value="{{ old('site_logo', \App\Models\Setting::get('site_logo', '')) }}" placeholder="https://example.com/logo.png or /images/logo.png" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-amber-400 focus:outline-none">
                                <span class="text-[11px] text-slate-500 mt-1 block">Custom header logo image. Replaces default lightning badge if provided.</span>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Favicon Icon (URL or Asset Path)</label>
                                <input type="text" name="site_favicon" value="{{ old('site_favicon', \App\Models\Setting::get('site_favicon', '')) }}" placeholder="https://example.com/favicon.ico or /favicon.ico" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-amber-400 focus:outline-none">
                                <span class="text-[11px] text-slate-500 mt-1 block">Browser tab icon (.ico or .png).</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">OpenGraph Social Share Image (OG Image)</label>
                            <input type="text" name="site_og_image" value="{{ old('site_og_image', \App\Models\Setting::get('site_og_image', '')) }}" placeholder="https://example.com/og-banner.png" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-amber-400 focus:outline-none">
                            <span class="text-[11px] text-slate-500 mt-1 block">Image displayed when sharing links on Twitter, Facebook, or WhatsApp (1200x630).</span>
                        </div>

                        {{-- Live Branding Preview Card --}}
                        <div class="p-4 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-2">
                            <div class="text-xs font-bold text-slate-400 uppercase">Live Header Logo Preview</div>
                            <div class="flex items-center space-x-3 p-3 bg-[#0B0F17] rounded-xl border border-slate-800">
                                @if(\App\Models\Setting::get('site_logo'))
                                    <img src="{{ \App\Models\Setting::get('site_logo') }}" class="h-8 object-contain" alt="Site Logo Preview">
                                @else
                                    <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-blue-600 to-sky-400 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    </div>
                                @endif
                                <span class="font-extrabold text-white">PROPHET<span class="text-[#38BDF8]">.AI</span></span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-800 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Telegram Bot Token</label>
                            <input type="text" name="telegram_bot_token" value="{{ old('telegram_bot_token', \App\Models\Setting::get('telegram_bot_token', config('services.telegram.bot_token'))) }}" placeholder="123456789:ABCdef..." class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-sky-400 focus:outline-none">
                            <span class="text-[11px] text-slate-500 mt-1 block">Bot token from @BotFather for sending subscriber DMs and channel posts.</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Bot Username</label>
                                <input type="text" name="telegram_bot_username" value="{{ old('telegram_bot_username', \App\Models\Setting::get('telegram_bot_username', config('services.telegram.bot_username'))) }}" placeholder="ProphetAIBot" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-sky-400 focus:outline-none">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Public Channel Username / ID</label>
                                <input type="text" name="telegram_channel_username" value="{{ old('telegram_channel_username', \App\Models\Setting::get('telegram_channel_username', config('services.telegram.channel_username'))) }}" placeholder="ProphetAIPicks" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-sky-400 focus:outline-none">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Engine Selection & Parameters -->
                <div class="space-y-4 pb-6 border-b border-slate-800">
                    <h3 class="text-sm font-extrabold text-white uppercase tracking-wider text-indigo-400">2. Prediction Engine Configuration</h3>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Prediction Provider Engine</label>
                        <select name="prediction_provider" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:border-indigo-400 focus:outline-none">
                            <option value="claude" {{ $settings['prediction_provider'] === 'claude' ? 'selected' : '' }}>Claude AI Engine (Recommended)</option>
                            <option value="poisson_xg" {{ $settings['prediction_provider'] === 'poisson_xg' ? 'selected' : '' }}>Poisson xG Statistical Matrix</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Min Confidence Floor</label>
                            <input type="number" step="0.01" min="0.10" max="0.99" name="min_confidence_threshold" value="{{ old('min_confidence_threshold', $settings['min_confidence_threshold']) }}" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-indigo-400 focus:outline-none" required>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Home Model Weight</label>
                            <input type="number" step="0.1" min="0.5" max="3.0" name="poisson_home_weight" value="{{ old('poisson_home_weight', $settings['poisson_home_weight']) }}" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-indigo-400 focus:outline-none" required>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Away Model Weight</label>
                            <input type="number" step="0.1" min="0.5" max="3.0" name="poisson_away_weight" value="{{ old('poisson_away_weight', $settings['poisson_away_weight']) }}" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-indigo-400 focus:outline-none" required>
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full py-4 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-extrabold text-sm shadow-xl shadow-sky-500/25 transition-all">
                    Save AI Engine Parameters & Keys &rarr;
                </button>
            </form>
        </div>
    </div>
@endsection
