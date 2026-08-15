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
                    
                    @php
                        // Credentials are stored encrypted and never rendered back into
                        // the page; a blank field on submit leaves the stored key alone.
                        $secretFields = [
                            'claude_api_key' => ['Claude API Key / Anthropic Key (AI Predictions)', 'sk-ant-api...', 'Used by <code>ClaudePredictionService</code> to compute market probabilities &amp; picks.'],
                            'gemini_api_key' => ['Gemini API Key (Match Narrative Previews)', 'AIzaSy...', 'Used by <code>PreviewGenerationService</code> to author 2-paragraph editorial previews.'],
                            'football_data_token' => ['football-data.org Token (Fixtures &amp; Results)', 'Your API token', 'Required for real fixtures and automatic result settling.'],
                            'telegram_bot_token' => ['Telegram Bot Token', '123456:ABC-DEF...', 'Used to post daily picks and deliver subscriber alerts.'],
                            'telegram_webhook_secret' => ['Telegram Webhook Secret', 'Random string', 'Verifies incoming Telegram updates. Required to accept the webhook.'],
                            'flutterwave_secret_key' => ['Flutterwave Secret Key', 'FLWSECK-...', 'Server-side key used to create and verify Flutterwave transactions.'],
                            'flutterwave_public_key' => ['Flutterwave Public Key', 'FLWPUBK-...', 'Public key for the Flutterwave checkout widget.'],
                            'flutterwave_webhook_hash' => ['Flutterwave Secret Hash', 'Your secret hash', 'The dashboard "Secret hash". Webhooks are rejected without it.'],
                            'paypal_client_id' => ['PayPal Client ID', 'AeA1QIZ...', 'REST app client id from the PayPal developer dashboard.'],
                            'paypal_secret' => ['PayPal Secret', 'EO422dn...', 'REST app secret. Paired with the client id to obtain access tokens.'],
                            'paypal_webhook_id' => ['PayPal Webhook ID', 'WH-2WR32...', 'Required to verify webhook signatures. Webhooks are rejected without it.'],
                        ];
                    @endphp

                    @foreach($secretFields as $field => [$label, $placeholder, $help])
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">
                                {!! $label !!}
                                @if($settings[$field . '_configured'] ?? false)
                                    <span class="ml-2 px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 text-[10px] normal-case">Configured</span>
                                @else
                                    <span class="ml-2 px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/30 text-[10px] normal-case">Not set</span>
                                @endif
                            </label>
                            <input type="password" name="{{ $field }}" autocomplete="new-password" value="" placeholder="{{ $settings[$field . '_configured'] ?? false ? 'Leave blank to keep the current value' : $placeholder }}" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-sky-400 focus:outline-none">
                            <span class="text-[11px] text-slate-500 mt-1 block">{!! $help !!}</span>
                        </div>
                    @endforeach

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
                                <span class="font-extrabold text-white">GUARANTEED <span class="text-[#38BDF8]">CORRECT</span></span>
                            </div>
                        </div>
                    </div>

                    {{-- The bot token and webhook secret are credentials and live in
                         the encrypted section above; only public identifiers here. --}}
                    <div class="pt-4 border-t border-slate-800 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Bot Username</label>
                            <input type="text" name="telegram_bot_username" value="{{ old('telegram_bot_username', $settings['telegram_bot_username']) }}" placeholder="GuaranteedCorrectBot" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-sky-400 focus:outline-none">
                            <span class="text-[11px] text-slate-500 mt-1 block">With or without the @.</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Public Channel Username</label>
                            <input type="text" name="telegram_channel_username" value="{{ old('telegram_channel_username', $settings['telegram_channel_username']) }}" placeholder="GuaranteedCorrectPicks" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-sky-400 focus:outline-none">
                            <span class="text-[11px] text-slate-500 mt-1 block">Drives the "Join VIP Channel" button.</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Channel ID</label>
                            <input type="text" name="telegram_channel_id" value="{{ old('telegram_channel_id', $settings['telegram_channel_id']) }}" placeholder="-1001234567890" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-sky-400 focus:outline-none">
                            <span class="text-[11px] text-slate-500 mt-1 block">Target for daily channel posts.</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Admin Support Contact</label>
                            <input type="text" name="telegram_admin_support_url" value="{{ old('telegram_admin_support_url', $settings['telegram_admin_support_url']) }}" placeholder="GuaranteedCorrectSupport" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-sky-400 focus:outline-none">
                            <span class="text-[11px] text-slate-500 mt-1 block">Handle or full invite link. Blank hides the button.</span>
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

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Claude Model</label>
                            <input type="text" name="claude_model" value="{{ old('claude_model', $settings['claude_model']) }}" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-indigo-400 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Reasoning Effort</label>
                            <select name="claude_effort" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:border-indigo-400 focus:outline-none">
                                @foreach(['low', 'medium', 'high', 'xhigh', 'max'] as $level)
                                    <option value="{{ $level }}" {{ $settings['claude_effort'] === $level ? 'selected' : '' }}>{{ ucfirst($level) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Publish Threshold</label>
                            <input type="number" step="0.01" min="0.10" max="0.99" name="min_confidence_threshold" value="{{ old('min_confidence_threshold', $settings['min_confidence_threshold']) }}" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-indigo-400 focus:outline-none" required>
                            <span class="text-[11px] text-slate-500 mt-1 block">Minimum probability for a pick to reach the public lists. Does not alter the stored probability.</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Home Advantage</label>
                            <input type="number" step="0.01" min="1.0" max="2.0" name="home_advantage" value="{{ old('home_advantage', $settings['home_advantage']) }}" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-indigo-400 focus:outline-none" required>
                            <span class="text-[11px] text-slate-500 mt-1 block">Multiplier on home expected goals (1.15 ≈ typical).</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Default League Average</label>
                            <input type="number" step="0.01" min="0.5" max="4.0" name="default_league_average" value="{{ old('default_league_average', $settings['default_league_average']) }}" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-indigo-400 focus:outline-none" required>
                            <span class="text-[11px] text-slate-500 mt-1 block">Goals per team per game for competitions with no baseline.</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Fixture Data Source</label>
                            <select name="fixture_provider" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:border-indigo-400 focus:outline-none">
                                <option value="football_data" {{ $settings['fixture_provider'] === 'football_data' ? 'selected' : '' }}>football-data.org (live fixtures)</option>
                                <option value="sample" {{ $settings['fixture_provider'] === 'sample' ? 'selected' : '' }}>Sample data (development only)</option>
                            </select>
                            <span class="text-[11px] text-slate-500 mt-1 block">Sample data is invented and will not run in production.</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Competition Codes</label>
                            <input type="text" name="football_data_competitions" value="{{ old('football_data_competitions', $settings['football_data_competitions']) }}" placeholder="PL,PD,SA,BL1,FL1,CL" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-indigo-400 focus:outline-none">
                            <span class="text-[11px] text-slate-500 mt-1 block">Comma-separated football-data.org codes. Blank uses the default set.</span>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Payment Gateways -->
                <div class="space-y-4 pb-6 border-b border-slate-800">
                    <h3 class="text-sm font-extrabold text-white uppercase tracking-wider text-emerald-400">3. Payment Gateways</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">PayPal Environment</label>
                            <select name="paypal_mode" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:border-emerald-400 focus:outline-none">
                                <option value="sandbox" {{ $settings['paypal_mode'] === 'sandbox' ? 'selected' : '' }}>Sandbox (testing)</option>
                                <option value="live" {{ $settings['paypal_mode'] === 'live' ? 'selected' : '' }}>Live (real payments)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">PayPal Plan ID</label>
                            <input type="text" name="paypal_plan_id" value="{{ old('paypal_plan_id', $settings['paypal_plan_id']) }}" placeholder="P-5ML4271244454362XMQIZHI" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-emerald-400 focus:outline-none">
                            <span class="text-[11px] text-slate-500 mt-1 block">The billing plan subscribers are enrolled on.</span>
                        </div>
                    </div>

                    <p class="text-[11px] text-slate-500">
                        Gateway keys are entered in section 1 above. Until a gateway's keys and webhook
                        identifier are set, its webhooks are rejected and checkout is disabled — payments
                        fail closed rather than granting free access.
                    </p>
                </div>

                <button type="submit" class="w-full py-4 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-extrabold text-sm shadow-xl shadow-sky-500/25 transition-all">
                    Save AI Engine Parameters & Keys &rarr;
                </button>
            </form>
        </div>
    </div>
@endsection
