@extends('layouts.app')

@section('title', 'AI Engine Parameter Control Panel — Admin Portal')

@section('content')
    <div class="max-w-3xl mx-auto py-6 space-y-6">
        {{-- Admin Navigation Bar --}}
        @include('admin.partials.nav')

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
            <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <!-- Section 1: API Credentials -->
                <div class="space-y-4 pb-6 border-b border-slate-800">
                    <h3 class="text-sm font-extrabold text-white uppercase tracking-wider text-sky-400">1. AI API Credentials</h3>
                    
                    @php
                        // Credentials are stored encrypted and never rendered back into
                        // the page; a blank field on submit leaves the stored key alone.
                        $secretFields = [
                            'claude_api_key' => ['Claude API Key / Anthropic Key (AI Predictions)', 'sk-ant-api...', 'Used by <code>ClaudePredictionService</code> to compute market probabilities &amp; picks.'],
                            'openai_api_key' => ['OpenAI / ChatGPT API Key (AI Predictions)', 'sk-...', 'Used by <code>OpenAiPredictionService</code> to compute market probabilities &amp; picks.'],
                            'gemini_api_key' => ['Gemini API Key (Previews &amp; Predictions)', 'AIzaSy...', 'Used by <code>PreviewGenerationService</code> for previews and <code>GeminiPredictionService</code> for predictions.'],
                            'kimi_api_key' => ['Moonshot Kimi API Key (AI Predictions)', 'sk-...', 'Used by <code>KimiPredictionService</code> to compute market probabilities &amp; picks.'],
                            'football_data_token' => ['football-data.org Token (Fixtures &amp; Results)', 'Your API token', 'Required for real fixtures and automatic result settling.'],
                            'telegram_bot_token' => ['Telegram Bot Token', '123456:ABC-DEF...', 'Used to post daily picks and deliver subscriber alerts.'],
                            'telegram_webhook_secret' => ['Telegram Webhook Secret', 'Random string', 'Verifies incoming Telegram updates. Webhook URL: <code class="text-sky-300 select-all">' . url('/webhooks/telegram') . '</code>'],
                            'flutterwave_secret_key' => ['Flutterwave Secret Key', 'FLWSECK-...', 'Server-side key used to create and verify Flutterwave transactions.'],
                            'flutterwave_public_key' => ['Flutterwave Public Key', 'FLWPUBK-...', 'Public key for the Flutterwave checkout widget.'],
                            'flutterwave_encryption_key' => ['Flutterwave Encryption Key', 'FLWSECK_... or encryption string', 'Optional 3DES encryption key from your Flutterwave API dashboard.'],
                            'flutterwave_webhook_hash' => ['Flutterwave Secret Hash', 'Your secret hash', 'The "Secret hash" from Flutterwave dashboard. Paste Webhook URL: <code class="text-amber-300 select-all">' . url('/webhooks/payment') . '</code> into Flutterwave Webhooks settings.'],
                            'paypal_client_id' => ['PayPal Client ID (Optional if PayPal Email is set)', 'AeA1QIZ...', 'Optional. REST app client id from the PayPal developer dashboard. (Not needed if PayPal Email is provided below).'],
                            'paypal_secret' => ['PayPal Secret (Optional if PayPal Email is set)', 'EO422dn...', 'Optional. REST app secret. (Not needed if PayPal Email is provided below).'],
                            'paypal_webhook_id' => ['PayPal Webhook ID (Optional if PayPal Email is set)', 'WH-2WR32...', 'Optional webhook ID. Webhook URL: <code class="text-emerald-300 select-all">' . url('/webhooks/payment') . '</code> (Not needed if PayPal Email is provided below).'],
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
                    <div class="pt-6 border-t border-slate-800 space-y-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-extrabold text-white uppercase tracking-wider text-amber-400">🎨 Site Branding, Logos & Icons</h3>
                                <p class="text-xs text-slate-400 mt-0.5">Upload brand files directly from your computer or specify custom asset URLs.</p>
                            </div>
                            <span class="px-2.5 py-1 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 text-[10px] font-bold">
                                Live Header Sync
                            </span>
                        </div>

                        @php
                            $currentLogo = \App\Models\Setting::get('site_logo', file_exists(public_path('images/logo.png')) ? asset('images/logo.png') : '');
                            $currentFavicon = \App\Models\Setting::get('site_favicon', file_exists(public_path('favicon.ico')) ? asset('favicon.ico') : '');
                            $currentOg = \App\Models\Setting::get('site_og_image', file_exists(public_path('images/og-banner.png')) ? asset('images/og-banner.png') : '');
                        @endphp

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Site Logo Card -->
                            <div class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-4">
                                <div class="flex items-center justify-between">
                                    <label class="block text-xs font-bold text-slate-200 uppercase">Primary Site Logo</label>
                                    <span class="text-[10px] text-slate-400 font-mono">PNG, SVG, WEBP, JPG</span>
                                </div>

                                {{-- Browse File Upload Button & Dropzone --}}
                                <div>
                                    <label for="logo_file_input" class="flex flex-col items-center justify-center p-4 border-2 border-dashed border-slate-700 hover:border-amber-400/80 rounded-xl cursor-pointer bg-slate-950/60 hover:bg-slate-950 transition-all group">
                                        <div class="flex items-center space-x-2 text-slate-300 group-hover:text-amber-400 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <span class="text-xs font-bold">Browse / Choose Logo File</span>
                                        </div>
                                        <span class="text-[10px] text-slate-500 mt-1" id="logo_filename_display">Click to select an image from your computer</span>
                                        <input type="file" id="logo_file_input" name="logo_file" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="hidden">
                                    </label>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold text-slate-400 mb-1">Or Logo URL / Asset Path</label>
                                    <input type="text" id="site_logo_input" name="site_logo" value="{{ old('site_logo', $currentLogo) }}" placeholder="{{ asset('images/logo.png') }}" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-xs font-mono focus:border-amber-400 focus:outline-none">
                                </div>
                            </div>

                            <!-- Favicon & Social Share Cards -->
                            <div class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-4">
                                <div class="flex items-center justify-between">
                                    <label class="block text-xs font-bold text-slate-200 uppercase">Favicon (.ico / .png)</label>
                                    <span class="text-[10px] text-slate-400 font-mono">32x32 / 64x64</span>
                                </div>

                                {{-- Browse Favicon File --}}
                                <div>
                                    <label for="favicon_file_input" class="flex flex-col items-center justify-center p-3 border-2 border-dashed border-slate-700 hover:border-amber-400/80 rounded-xl cursor-pointer bg-slate-950/60 hover:bg-slate-950 transition-all group">
                                        <div class="flex items-center space-x-2 text-slate-300 group-hover:text-amber-400 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                            <span class="text-xs font-bold">Browse Favicon File</span>
                                        </div>
                                        <span class="text-[10px] text-slate-500 mt-0.5" id="favicon_filename_display">Click to upload favicon</span>
                                        <input type="file" id="favicon_file_input" name="favicon_file" accept=".ico,image/png,image/svg+xml" class="hidden">
                                    </label>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold text-slate-400 mb-1">Favicon URL / Path</label>
                                    <input type="text" id="site_favicon_input" name="site_favicon" value="{{ old('site_favicon', $currentFavicon) }}" placeholder="/favicon.ico" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-xs font-mono focus:border-amber-400 focus:outline-none">
                                </div>
                            </div>
                        </div>

                        <!-- OpenGraph Social Share Card -->
                        <div class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-slate-200 uppercase">OpenGraph Social Banner (1200x630)</label>
                                <span class="text-[10px] text-slate-400 font-mono">Twitter / WhatsApp Preview</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <label for="og_image_file_input" class="flex flex-col items-center justify-center p-3 border-2 border-dashed border-slate-700 hover:border-amber-400/80 rounded-xl cursor-pointer bg-slate-950/60 hover:bg-slate-950 transition-all group">
                                    <div class="flex items-center space-x-2 text-slate-300 group-hover:text-amber-400 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span class="text-xs font-bold">Browse OG Banner Image</span>
                                    </div>
                                    <span class="text-[10px] text-slate-500 mt-0.5" id="og_filename_display">Click to upload social share banner</span>
                                    <input type="file" id="og_image_file_input" name="og_image_file" accept="image/png,image/jpeg,image/webp" class="hidden">
                                </label>
                                <div>
                                    <input type="text" id="site_og_image_input" name="site_og_image" value="{{ old('site_og_image', $currentOg) }}" placeholder="{{ asset('images/og-banner.png') }}" class="w-full px-3.5 py-3 rounded-xl bg-slate-950 border border-slate-700 text-white text-xs font-mono focus:border-amber-400 focus:outline-none">
                                    <span class="text-[10px] text-slate-500 mt-1 block">Displayed on Twitter, WhatsApp and Telegram share embeds.</span>
                                </div>
                            </div>
                        </div>

                        {{-- Live Branding Header Preview Card --}}
                        <div class="p-5 rounded-2xl bg-gradient-to-b from-slate-900 to-[#0B0F17] border border-slate-700/80 shadow-2xl space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="text-xs font-extrabold text-sky-400 uppercase tracking-wider flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                    Live Header Integration Preview
                                </div>
                                <span class="text-[11px] text-slate-400">Renders exactly as seen by visitors</span>
                            </div>

                            <div class="p-4 bg-[#0B0F17] rounded-xl border border-slate-800 flex items-center justify-between shadow-inner">
                                <div class="flex items-center space-x-3" id="live_logo_container">
                                    <img id="live_logo_img" src="{{ $currentLogo ?: asset('images/logo.png') }}" class="h-11 w-auto max-w-[220px] object-contain drop-shadow-[0_0_12px_rgba(56,189,248,0.35)]" alt="Site Logo Preview" onerror="this.style.display='none'; document.getElementById('live_logo_fallback').style.display='flex';">
                                    <div id="live_logo_fallback" style="display: none;" class="flex items-center space-x-2">
                                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-600 to-sky-400 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                        </div>
                                        <span class="font-extrabold text-white">GUARANTEED <span class="text-[#38BDF8]">CORRECT</span></span>
                                    </div>
                                </div>
                                <div class="hidden sm:flex items-center space-x-4 text-xs font-medium text-slate-400">
                                    <span class="text-[#38BDF8] font-bold">Home</span>
                                    <span>Top Picks <span class="text-[9px] px-1 py-0.5 rounded bg-sky-500/20 text-sky-400">PRO</span></span>
                                    <span class="text-[#F5A623]">Expert Picks</span>
                                    <span>Track Record</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="px-3 py-1 text-xs font-bold rounded-lg bg-gradient-to-r from-sky-500 to-blue-600 text-white shadow-md shadow-sky-500/20">Upgrade PRO</span>
                                </div>
                            </div>
                        </div>
                    </div                    <!-- Telegram Community & Join VIP Banner Section -->
                    <div class="pt-6 border-t border-slate-800 space-y-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-extrabold text-white uppercase tracking-wider text-sky-400">✈️ Telegram Community & Join VIP Banner</h3>
                                <p class="text-xs text-slate-400 mt-0.5">Customize the "Join VIP Channel" banner shown on home, account & leaderboard pages, plus bot automations.</p>
                            </div>
                            <span class="px-2.5 py-1 rounded-full bg-sky-500/10 border border-sky-500/30 text-sky-400 text-[10px] font-bold">
                                Community Integration
                            </span>
                        </div>

                        {{-- Hidden presence input to allow unchecking the box --}}
                        <input type="hidden" name="telegram_banner_present" value="1">

                        <label class="flex items-start gap-3 p-3.5 rounded-2xl bg-slate-950/60 border border-slate-800 cursor-pointer hover:border-sky-500/40 transition-colors">
                            <input type="checkbox" name="telegram_banner_enabled" value="1" @checked(old('telegram_banner_enabled', $settings['telegram_banner_enabled']) == '1') class="mt-0.5 w-4 h-4 rounded bg-slate-900 border-slate-600 text-sky-500 focus:ring-sky-500" id="telegram_banner_enabled_input">
                            <span class="text-[11px] text-slate-400 leading-relaxed">
                                <span class="block text-xs font-bold text-slate-200 uppercase mb-0.5">Display VIP Channel Banner on Public Pages</span>
                                Shows the high-conversion Telegram community promotion box on the Home page, Account page, and Expert Leaderboard.
                            </span>
                        </label>

                        <!-- Banner Text & CTA Customization Card -->
                        <div class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-4">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-extrabold text-white uppercase tracking-wider">Banner Content & Copy</h4>
                                <span class="text-[10px] text-slate-400 font-mono">Live Customization</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Badge Text</label>
                                    <input type="text" id="telegram_badge_input" name="telegram_banner_badge" value="{{ old('telegram_banner_badge', $settings['telegram_banner_badge']) }}" placeholder="OFFICIAL TELEGRAM COMMUNITY" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-xs focus:border-sky-400 focus:outline-none">
                                    <span class="text-[10px] text-slate-500 mt-1 block">Top small pill badge text.</span>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Banner Icon / Emoji</label>
                                    <input type="text" id="telegram_icon_input" name="telegram_banner_icon" value="{{ old('telegram_banner_icon', $settings['telegram_banner_icon']) }}" placeholder="✈️" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-xs text-center focus:border-sky-400 focus:outline-none">
                                    <span class="text-[10px] text-slate-500 mt-1 block">Emoji or icon (e.g. ✈️, ⚽, 🔥).</span>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Join Button Label</label>
                                    <input type="text" id="telegram_cta_input" name="telegram_banner_cta_text" value="{{ old('telegram_banner_cta_text', $settings['telegram_banner_cta_text']) }}" placeholder="Join VIP Channel" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-xs font-bold focus:border-sky-400 focus:outline-none">
                                    <span class="text-[10px] text-slate-500 mt-1 block">Primary CTA button text.</span>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Banner Headline</label>
                                <input type="text" id="telegram_headline_input" name="telegram_banner_headline" value="{{ old('telegram_banner_headline', $settings['telegram_banner_headline']) }}" placeholder="Get Instant AI Predictions & Admin Support on Telegram" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-xs focus:border-sky-400 focus:outline-none">
                                <span class="text-[10px] text-slate-500 mt-1 block">Main headline text in the banner.</span>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Banner Description</label>
                                <textarea id="telegram_desc_input" name="telegram_banner_description" rows="2" placeholder="Join our public channel for instant match alerts or contact live admin support anytime." class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-xs focus:border-sky-400 focus:outline-none">{{ old('telegram_banner_description', $settings['telegram_banner_description']) }}</textarea>
                                <span class="text-[10px] text-slate-500 mt-0.5 block">Subheading explaining community benefits.</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-800">
                                <div>
                                    <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Support Button Label</label>
                                    <input type="text" id="telegram_support_cta_input" name="telegram_support_cta_text" value="{{ old('telegram_support_cta_text', $settings['telegram_support_cta_text']) }}" placeholder="💬 Admin Support" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-xs focus:border-sky-400 focus:outline-none">
                                    <span class="text-[10px] text-slate-500 mt-1 block">Label for the secondary admin support button.</span>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Admin Support Contact / URL</label>
                                    <input type="text" id="telegram_support_url_input" name="telegram_admin_support_url" value="{{ old('telegram_admin_support_url', $settings['telegram_admin_support_url']) }}" placeholder="GuaranteedCorrectSupport" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-xs font-mono focus:border-sky-400 focus:outline-none">
                                    <span class="text-[10px] text-slate-500 mt-1 block">Username handle or full link. Blank hides support button.</span>
                                </div>
                            </div>
                        </div>

                        <!-- Channel Handles & Automation Identifiers -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Channel Username / Invite Link</label>
                                <input type="text" id="telegram_channel_input" name="telegram_channel_username" value="{{ old('telegram_channel_username', $settings['telegram_channel_username']) }}" placeholder="GuaranteedCorrectPicks" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-sky-400 focus:outline-none">
                                <span class="text-[11px] text-slate-500 mt-1 block">Public @handle or invite URL (<code class="text-sky-300">https://t.me/...</code>).</span>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Channel ID (Posting)</label>
                                <input type="text" name="telegram_channel_id" value="{{ old('telegram_channel_id', $settings['telegram_channel_id']) }}" placeholder="-1001234567890" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-sky-400 focus:outline-none">
                                <span class="text-[11px] text-slate-500 mt-1 block">Numeric target ID for automated daily channel posts.</span>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Bot Username</label>
                                <input type="text" name="telegram_bot_username" value="{{ old('telegram_bot_username', $settings['telegram_bot_username']) }}" placeholder="GuaranteedCorrectBot" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-sky-400 focus:outline-none">
                                <span class="text-[11px] text-slate-500 mt-1 block">Displayed on user account page for alerts bot.</span>
                            </div>
                        </div>

                        <!-- Live Banner Preview Card -->
                        <div class="p-5 rounded-2xl bg-gradient-to-b from-slate-900 to-[#0B0F17] border border-slate-700/80 shadow-2xl space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="text-xs font-extrabold text-sky-400 uppercase tracking-wider flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                    Live Join VIP Banner Preview
                                </div>
                                <span class="text-[11px] text-slate-400">Updates live as you type</span>
                            </div>

                            <div class="p-4 rounded-2xl bg-gradient-to-r from-sky-950/60 via-slate-900/90 to-indigo-950/60 border border-sky-500/30 shadow-xl relative overflow-hidden" id="preview_tg_box">
                                <div class="flex flex-col md:flex-row items-center justify-between gap-4 relative z-10">
                                    <div class="flex items-center space-x-4">
                                        <div class="p-3 bg-sky-500/20 text-sky-400 rounded-2xl border border-sky-500/40 text-2xl flex-shrink-0" id="preview_tg_icon">
                                            {{ $settings['telegram_banner_icon'] ?: '✈️' }}
                                        </div>
                                        <div>
                                            <div class="inline-flex items-center space-x-2 text-[10px] font-extrabold tracking-wider uppercase px-2.5 py-0.5 rounded-full bg-sky-500/20 text-sky-300 border border-sky-500/30 mb-1" id="preview_tg_badge_container">
                                                <span id="preview_tg_badge">{{ $settings['telegram_banner_badge'] ?: 'OFFICIAL TELEGRAM COMMUNITY' }}</span>
                                            </div>
                                            <h3 class="text-base sm:text-lg font-black text-white" id="preview_tg_headline">{{ $settings['telegram_banner_headline'] ?: 'Get Instant AI Predictions & Admin Support on Telegram' }}</h3>
                                            <p class="text-xs text-slate-300" id="preview_tg_description">{{ $settings['telegram_banner_description'] ?: 'Join our public channel for instant match alerts or contact live admin support anytime.' }}</p>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto justify-start md:justify-end">
                                        <div class="px-4 py-2.5 rounded-xl bg-sky-500 text-slate-950 font-black text-xs shadow-lg shadow-sky-500/25 flex items-center justify-center space-x-1.5 whitespace-nowrap">
                                            <span id="preview_tg_cta">{{ $settings['telegram_banner_cta_text'] ?: 'Join VIP Channel' }}</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                        </div>
                                        <div class="px-4 py-2.5 rounded-xl bg-slate-800/90 text-slate-200 font-bold text-xs border border-slate-700 flex items-center justify-center space-x-1.5 whitespace-nowrap" id="preview_tg_support_btn">
                                            <span id="preview_tg_support_cta">{{ $settings['telegram_support_cta_text'] ?: '💬 Admin Support' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SportyBet Booking Code Integration Section -->
                    <div class="pt-6 border-t border-slate-800 space-y-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-extrabold text-white uppercase tracking-wider text-red-400">🔴 SportyBet Top 5 Booking Code Integration</h3>
                                <p class="text-xs text-slate-400 mt-0.5">Manage daily SportyBet accumulator booking code, copy buttons, direct betslip URLs & API sessions.</p>
                            </div>
                            <span class="px-2.5 py-1 rounded-full bg-red-500/10 border border-red-500/30 text-red-400 text-[10px] font-bold">
                                SportyBet Integration
                            </span>
                        </div>

                        {{-- Hidden presence input to allow unchecking the box --}}
                        <input type="hidden" name="sportybet_booking_present" value="1">

                        <label class="flex items-start gap-3 p-3.5 rounded-2xl bg-slate-950/60 border border-slate-800 cursor-pointer hover:border-red-500/40 transition-colors">
                            <input type="checkbox" name="sportybet_booking_code_enabled" value="1" @checked(old('sportybet_booking_code_enabled', $settings['sportybet_booking_code_enabled']) == '1') class="mt-0.5 w-4 h-4 rounded bg-slate-900 border-slate-600 text-red-500 focus:ring-red-500">
                            <span class="text-[11px] text-slate-400 leading-relaxed">
                                <span class="block text-xs font-bold text-slate-200 uppercase mb-0.5">Display SportyBet Booking Code &amp; Copy Button</span>
                                Shows the high-converting SportyBet booking code card with 1-click clipboard copy on the Home page and Top Picks page for the AI Top 5 picks.
                            </span>
                        </label>

                        <div class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-4">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-extrabold text-white uppercase tracking-wider">Booking Code Configuration</h4>
                                <span class="text-[10px] text-slate-400 font-mono">Daily Slips &amp; Endpoints</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Today's SportyBet Booking Code</label>
                                    <input type="text" name="sportybet_top5_booking_code" value="{{ old('sportybet_top5_booking_code', $settings['sportybet_top5_booking_code']) }}" placeholder="e.g. SB-6A9F12 or SPB-XYZ123" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-yellow-400 text-xs font-mono font-bold uppercase tracking-wider focus:border-red-400 focus:outline-none">
                                    <span class="text-[10px] text-slate-500 mt-1 block">Custom SportyBet code override. If left blank, a daily deterministic code is generated.</span>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-300 uppercase mb-2">SportyBet Region / Country</label>
                                    <select name="sportybet_region" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-xs focus:border-red-400 focus:outline-none">
                                        <option value="ng" @selected(old('sportybet_region', $settings['sportybet_region']) === 'ng')>Nigeria (sportybet.com/ng)</option>
                                        <option value="gh" @selected(old('sportybet_region', $settings['sportybet_region']) === 'gh')>Ghana (sportybet.com/gh)</option>
                                        <option value="ke" @selected(old('sportybet_region', $settings['sportybet_region']) === 'ke')>Kenya (sportybet.com/ke)</option>
                                        <option value="ug" @selected(old('sportybet_region', $settings['sportybet_region']) === 'ug')>Uganda (sportybet.com/ug)</option>
                                        <option value="zm" @selected(old('sportybet_region', $settings['sportybet_region']) === 'zm')>Zambia (sportybet.com/zm)</option>
                                        <option value="tz" @selected(old('sportybet_region', $settings['sportybet_region']) === 'tz')>Tanzania (sportybet.com/tz)</option>
                                    </select>
                                    <span class="text-[10px] text-slate-500 mt-1 block">Determines the booking code endpoint and load URL domain.</span>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Custom Betslip URL (Optional)</label>
                                    <input type="text" name="sportybet_custom_url" value="{{ old('sportybet_custom_url', $settings['sportybet_custom_url']) }}" placeholder="https://.../?shareCode={code}" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-xs font-mono focus:border-red-400 focus:outline-none">
                                    <span class="text-[10px] text-slate-500 mt-1 block">Optional affiliate link. Use <code>{code}</code> to dynamically substitute booking code.</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-slate-800">
                                <div>
                                    <label class="block text-xs font-bold text-slate-300 uppercase mb-2">
                                        SportyBet Session Cookie (Optional API Integration)
                                        @if($settings['sportybet_cookie_configured'] ?? false)
                                            <span class="ml-2 px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 text-[10px] normal-case">Configured</span>
                                        @else
                                            <span class="ml-2 px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/30 text-[10px] normal-case">Not set</span>
                                        @endif
                                    </label>
                                    <input type="password" name="sportybet_cookie" autocomplete="new-password" value="" placeholder="{{ $settings['sportybet_cookie_configured'] ?? false ? 'Leave blank to keep current session cookie' : 'Paste active browser session cookie' }}" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-xs font-mono focus:border-red-400 focus:outline-none">
                                    <span class="text-[10px] text-slate-500 mt-1 block">Used by <code>SportyBetService</code> for automated slip decoding and requests. Encrypted at rest.</span>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-300 uppercase mb-2">
                                        X-Sporty-Token (Optional API Token)
                                        @if($settings['sportybet_token_configured'] ?? false)
                                            <span class="ml-2 px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 text-[10px] normal-case">Configured</span>
                                        @else
                                            <span class="ml-2 px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/30 text-[10px] normal-case">Not set</span>
                                        @endif
                                    </label>
                                    <input type="password" name="sportybet_token" autocomplete="new-password" value="" placeholder="{{ $settings['sportybet_token_configured'] ?? false ? 'Leave blank to keep current token' : 'Paste X-Sporty-Token header if required' }}" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-xs font-mono focus:border-red-400 focus:outline-none">
                                    <span class="text-[10px] text-slate-500 mt-1 block">API verification token if required by SportyBet API endpoints. Encrypted at rest.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 1b: Outgoing Mail -->
                <div class="space-y-4 pb-6 border-b border-slate-800">
                    <h3 class="text-sm font-extrabold text-white uppercase tracking-wider text-rose-400">📧 Outgoing Mail (SMTP)</h3>
                    <p class="text-[11px] text-slate-500 -mt-2">
                        Password resets and email verification depend on this. Anything left blank falls back to
                        <code>.env</code>. Create the mailbox in cPanel &rsaquo; Email Accounts, then use its SMTP details.
                        Send a test from <a href="{{ route('admin.system') }}" class="text-sky-400 hover:underline">System</a>.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="lg:col-span-2">
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">SMTP Host</label>
                            <input type="text" name="mail_host" value="{{ old('mail_host', $settings['mail_host']) }}" placeholder="mail.yourdomain.com" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-sky-400 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Port</label>
                            <input type="number" name="mail_port" value="{{ old('mail_port', $settings['mail_port']) }}" placeholder="587" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-sky-400 focus:outline-none">
                            <span class="text-[11px] text-slate-500 mt-1 block">587 = TLS, 465 = SSL.</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Encryption</label>
                            <select name="mail_scheme" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:border-sky-400 focus:outline-none">
                                <option value="">Auto (from port)</option>
                                <option value="tls" @selected(old('mail_scheme', $settings['mail_scheme']) === 'tls')>STARTTLS</option>
                                <option value="ssl" @selected(old('mail_scheme', $settings['mail_scheme']) === 'ssl')>SSL</option>
                            </select>
                        </div>

                        <div class="lg:col-span-2">
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">SMTP Username</label>
                            <input type="text" name="mail_username" value="{{ old('mail_username', $settings['mail_username']) }}" placeholder="noreply@yourdomain.com" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-sky-400 focus:outline-none">
                        </div>

                        <div class="lg:col-span-2">
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">
                                SMTP Password
                                @if($settings['mail_password_configured'] ?? false)
                                    <span class="ml-2 px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 text-[10px] normal-case">Configured</span>
                                @else
                                    <span class="ml-2 px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/30 text-[10px] normal-case">Not set</span>
                                @endif
                            </label>
                            <input type="password" name="mail_password" autocomplete="new-password" value="" placeholder="{{ ($settings['mail_password_configured'] ?? false) ? 'Leave blank to keep the current value' : 'Mailbox password' }}" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-sky-400 focus:outline-none">
                        </div>

                        <div class="lg:col-span-2">
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">From Address</label>
                            <input type="email" name="mail_from_address" value="{{ old('mail_from_address', $settings['mail_from_address']) }}" placeholder="noreply@yourdomain.com" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-sky-400 focus:outline-none">
                            <span class="text-[11px] text-slate-500 mt-1 block">Must be a real mailbox on your domain, or Gmail will reject the mail.</span>
                        </div>

                        <div class="lg:col-span-2">
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">From Name</label>
                            <input type="text" name="mail_from_name" value="{{ old('mail_from_name', $settings['mail_from_name']) }}" placeholder="{{ config('app.name') }}" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:border-sky-400 focus:outline-none">
                        </div>
                    </div>
                </div>

                <!-- Section 2: Engine Selection & Parameters -->
                <div class="space-y-4 pb-6 border-b border-slate-800">
                    <h3 class="text-sm font-extrabold text-white uppercase tracking-wider text-indigo-400">2. Prediction Engine Configuration</h3>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Prediction Provider Engine</label>
                        <select name="prediction_provider" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:border-indigo-400 focus:outline-none">
                            <option value="claude" {{ in_array($settings['prediction_provider'], ['claude'], true) ? 'selected' : '' }}>Claude AI Engine (Anthropic)</option>
                            <option value="chatgpt" {{ in_array($settings['prediction_provider'], ['chatgpt', 'openai'], true) ? 'selected' : '' }}>ChatGPT / OpenAI Engine</option>
                            <option value="gemini" {{ $settings['prediction_provider'] === 'gemini' ? 'selected' : '' }}>Google Gemini AI Engine</option>
                            <option value="kimi" {{ $settings['prediction_provider'] === 'kimi' ? 'selected' : '' }}>Moonshot Kimi AI Engine</option>
                            <option value="poisson_xg" {{ $settings['prediction_provider'] === 'poisson_xg' ? 'selected' : '' }}>Poisson xG Statistical Matrix</option>
                        </select>
                        <span class="text-[11px] text-slate-500 mt-1 block">Active engine used by <code>PredictionService</code> to evaluate upcoming fixtures. Falls back to Poisson if the selected provider is unconfigured.</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Claude Model</label>
                            <input type="text" name="claude_model" value="{{ old('claude_model', $settings['claude_model']) }}" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-indigo-400 focus:outline-none">
                            <span class="text-[11px] text-slate-500 mt-1 block">Default: <code>{{ \App\Services\ClaudePredictionService::DEFAULT_MODEL }}</code></span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Claude Reasoning Effort</label>
                            <select name="claude_effort" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:border-indigo-400 focus:outline-none">
                                @foreach(['low', 'medium', 'high', 'xhigh', 'max'] as $level)
                                    <option value="{{ $level }}" {{ $settings['claude_effort'] === $level ? 'selected' : '' }}>{{ ucfirst($level) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">ChatGPT / OpenAI Model</label>
                            <input type="text" name="openai_model" value="{{ old('openai_model', $settings['openai_model']) }}" placeholder="{{ \App\Services\OpenAiPredictionService::DEFAULT_MODEL }}" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-indigo-400 focus:outline-none">
                            <span class="text-[11px] text-slate-500 mt-1 block">Default: <code>{{ \App\Services\OpenAiPredictionService::DEFAULT_MODEL }}</code></span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Gemini Prediction Model</label>
                            <input type="text" name="gemini_prediction_model" value="{{ old('gemini_prediction_model', $settings['gemini_prediction_model']) }}" placeholder="{{ \App\Services\GeminiPredictionService::DEFAULT_MODEL }}" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-indigo-400 focus:outline-none">
                            <span class="text-[11px] text-slate-500 mt-1 block">Default: <code>{{ \App\Services\GeminiPredictionService::DEFAULT_MODEL }}</code></span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Moonshot Kimi Model</label>
                            <input type="text" name="kimi_model" value="{{ old('kimi_model', $settings['kimi_model']) }}" placeholder="{{ \App\Services\KimiPredictionService::DEFAULT_MODEL }}" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-indigo-400 focus:outline-none">
                            <span class="text-[11px] text-slate-500 mt-1 block">Default: <code>{{ \App\Services\KimiPredictionService::DEFAULT_MODEL }}</code></span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Gemini Preview Model</label>
                            <input type="text" name="gemini_model" value="{{ old('gemini_model', $settings['gemini_model']) }}" placeholder="{{ \App\Services\PreviewGenerationService::DEFAULT_MODEL }}" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-indigo-400 focus:outline-none">
                            <span class="text-[11px] text-slate-500 mt-1 block">Used by <code>PreviewGenerationService</code> for match narratives.</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Global Publish Threshold</label>
                            <input type="number" step="0.01" min="0.10" max="0.99" name="min_confidence_threshold" value="{{ old('min_confidence_threshold', $settings['min_confidence_threshold']) }}" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-indigo-400 focus:outline-none" required>
                            <span class="text-[11px] text-slate-500 mt-1 block">Default fallback probability for a pick to reach public Top 10 / AI 5 lists.</span>
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

                    <!-- Per-Market Confidence Threshold Overrides -->
                    <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-xs font-extrabold text-indigo-400 uppercase tracking-wider">🎯 Per-Market Promotion Thresholds</h4>
                                <p class="text-[11px] text-slate-400 mt-0.5">Customize individual promotion floors for all 8 prediction markets (leave blank to use market default).</p>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full bg-indigo-500/10 border border-indigo-500/30 text-indigo-400 text-[10px] font-bold">
                                Fine-Grained Filters
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            @foreach(\App\Support\MarketRegistry::all() as $mKey => $mDef)
                                <div class="p-3 rounded-xl bg-slate-950/80 border border-slate-800 space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-white">{{ $mDef->short }}</span>
                                        <span class="text-[10px] text-slate-500 font-mono">Def: {{ $mDef->threshold ? round($mDef->threshold * 100) . '%' : 'Global' }}</span>
                                    </div>
                                    <div class="text-[10px] text-slate-400 truncate">{{ $mDef->label }}</div>
                                    <input type="number" step="0.01" min="0.10" max="0.99"
                                           name="market_threshold_{{ $mKey }}"
                                           value="{{ old('market_threshold_' . $mKey, $settings['market_threshold_' . $mKey] ?? '') }}"
                                           placeholder="{{ $mDef->threshold ? round($mDef->threshold, 2) : $settings['min_confidence_threshold'] }}"
                                           class="w-full px-2.5 py-1.5 rounded-lg bg-slate-900 border border-slate-700 text-white text-xs font-mono focus:border-indigo-400 focus:outline-none">
                                </div>
                            @endforeach
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

                    {{-- Publishing window --}}
                    <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-4">
                        <h4 class="text-xs font-extrabold text-sky-400 uppercase tracking-wider">📅 Preview Publishing Window</h4>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-start">
                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Publish Previews Days Ahead</label>
                                <input type="number" min="1" max="{{ \App\Models\GameMatch::MAX_PREVIEW_LEAD_DAYS }}" step="1" name="preview_lead_days" value="{{ old('preview_lead_days', $settings['preview_lead_days']) }}" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-sky-400 focus:outline-none" required>
                                <span class="text-[11px] text-slate-500 mt-1 block">
                                    How far ahead fixtures are ingested and previews published. 7 is the usual setting;
                                    {{ \App\Models\GameMatch::MAX_PREVIEW_LEAD_DAYS }} is the hard ceiling.
                                </span>
                            </div>

                            <div class="text-[11px] text-slate-400 leading-relaxed pt-7">
                                Raising this fetches more fixtures per run, which costs one model call per new fixture and
                                consumes more of the football-data quota. Beyond about a week, kickoff times still move and
                                late fixtures are still being added, so a preview written that far out is often stale by matchday.
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-start pt-2 border-t border-slate-800">
                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Home Page Fixture Days</label>
                                <input type="number" min="1" max="{{ \App\Models\GameMatch::MAX_PREVIEW_LEAD_DAYS }}" step="1" name="home_fixture_days" value="{{ old('home_fixture_days', $settings['home_fixture_days']) }}" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-sky-400 focus:outline-none">
                                <span class="text-[11px] text-slate-500 mt-1 block">
                                    How much of that window the <strong>home page</strong> features. Lower this to keep the
                                    front page tight without withdrawing anything from the site.
                                </span>
                            </div>

                            <div class="text-[11px] text-slate-400 leading-relaxed pt-7">
                                Fixtures beyond this still have their own pages, predictions, previews and sitemap entries —
                                they are simply not on the front page. Cannot exceed the publishing window above, since the
                                home page cannot list fixtures that have not been ingested yet.
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-start pt-2 border-t border-slate-800">
                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Preview Refresh Days</label>
                                <input type="number" min="1" max="{{ \App\Models\GameMatch::MAX_PREVIEW_LEAD_DAYS }}" step="1" name="preview_refresh_days" value="{{ old('preview_refresh_days', $settings['preview_refresh_days']) }}" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-sky-400 focus:outline-none">
                                <span class="text-[11px] text-slate-500 mt-1 block">
                                    How close to kickoff each preview gets its one rewrite, on data that has settled.
                                    2 days is the default.
                                </span>
                            </div>

                            <div class="text-[11px] text-slate-400 leading-relaxed pt-7">
                                Each preview is written once when the fixture is ingested and rewritten once inside this
                                window — two model calls per fixture, not one per night. Previews that fell back to
                                boilerplate because the model was unreachable are retried on the next run regardless.
                            </div>
                        </div>
                    </div>

                    {{-- Homepage Layout & Hero Visibility --}}
                    <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-4">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-extrabold text-sky-400 uppercase tracking-wider">🏠 Homepage Track Record &amp; Hero Visibility</h4>
                            <span class="px-2 py-0.5 rounded-full bg-sky-500/10 border border-sky-500/30 text-sky-400 text-[10px] font-bold">
                                Live Toggle
                            </span>
                        </div>

                        {{-- Marks this section as submitted for checkboxes --}}
                        <input type="hidden" name="homepage_toggles_present" value="1">

                        <div class="space-y-3">
                            <label class="flex items-start gap-3 p-3.5 rounded-xl bg-slate-950/60 border border-slate-800 cursor-pointer hover:border-sky-500/30 transition-colors">
                                <input type="checkbox" name="home_hero_stats_enabled" value="1" @checked(old('home_hero_stats_enabled', $settings['home_hero_stats_enabled'] ?? '1') == '1') class="mt-0.5 w-4 h-4 rounded bg-slate-900 border-slate-600 text-sky-500 focus:ring-sky-500">
                                <span class="text-[11px] text-slate-400 leading-relaxed">
                                    <span class="block text-xs font-bold text-slate-200 uppercase mb-0.5">Show Homepage Trust Stats Strip (Hit Rates Grid)</span>
                                    Displays the 5-column accuracy metrics box ("Overall AI Hit Rate", "WIN Market", etc.) on the homepage hero. Uncheck this box if you want to temporarily hide the metrics grid while accumulating settlement data.
                                </span>
                            </label>

                            <label class="flex items-start gap-3 p-3.5 rounded-xl bg-slate-950/60 border border-slate-800 cursor-pointer hover:border-sky-500/30 transition-colors">
                                <input type="checkbox" name="home_hero_section_enabled" value="1" @checked(old('home_hero_section_enabled', $settings['home_hero_section_enabled'] ?? '1') == '1') class="mt-0.5 w-4 h-4 rounded bg-slate-900 border-slate-600 text-sky-500 focus:ring-sky-500">
                                <span class="text-[11px] text-slate-400 leading-relaxed">
                                    <span class="block text-xs font-bold text-slate-200 uppercase mb-0.5">Show Entire Homepage Hero Headline &amp; Intro</span>
                                    Controls the main headline ("AI Football Predictions Backed By Transparent Track Records") and CTA buttons at the top of the homepage.
                                </span>
                            </label>
                        </div>
                    </div>

                    {{-- AI newsroom --}}
                    <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-4">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-extrabold text-amber-400 uppercase tracking-wider">📰 AI Newsroom Provider & Model</h4>
                            <span class="px-2 py-0.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 text-[10px] font-bold">
                                Multi-Model Supported
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-2">AI Newsroom Engine</label>
                                <select name="news_ai_provider" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:border-amber-400 focus:outline-none">
                                    <option value="gemini" @selected(old('news_ai_provider', $settings['news_ai_provider'] ?? 'gemini') === 'gemini')>Google Gemini (Fast, High Quality &amp; Free Tier) [Recommended]</option>
                                    <option value="claude" @selected(old('news_ai_provider', $settings['news_ai_provider'] ?? '') === 'claude')>Anthropic Claude (Deep Editorial Tone)</option>
                                    <option value="openai" @selected(old('news_ai_provider', $settings['news_ai_provider'] ?? '') === 'openai')>OpenAI / ChatGPT (GPT-4o / GPT-4o-mini)</option>
                                </select>
                                <span class="text-[11px] text-slate-500 mt-1 block">Choose the AI provider used to write and rewrite football newsroom articles.</span>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Article Model Override</label>
                                <input type="text" name="blog_ai_model" value="{{ old('blog_ai_model', $settings['blog_ai_model']) }}" placeholder="e.g. gemini-2.5-flash or gemini-2.5-pro" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-amber-400 focus:outline-none">
                                <span class="text-[11px] text-slate-500 mt-1 block">Blank defaults to <code>gemini-2.5-flash</code> for Gemini, or <code>claude-opus-5</code> for Claude.</span>
                            </div>
                        </div>

                        {{-- Marks this section as submitted, so the unticked
                             checkbox can be told apart from "not on this form". --}}
                        <input type="hidden" name="ai_posts_autopublish_present" value="1">

                        <label class="flex items-start gap-3 p-3 rounded-xl bg-slate-950/60 border border-slate-800 cursor-pointer">
                            <input type="checkbox" name="ai_posts_autopublish" value="1" @checked(old('ai_posts_autopublish', $settings['ai_posts_autopublish']) == '1') class="mt-0.5 w-4 h-4 rounded bg-slate-900 border-slate-600 text-amber-500 focus:ring-amber-500">
                            <span class="text-[11px] text-slate-400 leading-relaxed">
                                <span class="block text-xs font-bold text-slate-200 uppercase mb-0.5">Publish AI articles automatically</span>
                                Off by default: generated articles land as drafts for a human to read before they go live.
                                Turn this on only if you are comfortable with unreviewed model copy appearing on a gambling site.
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Section 3: Subscription Pricing & Payment Gateways -->
                <div class="space-y-6 pb-6 border-b border-slate-800">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-extrabold text-white uppercase tracking-wider text-emerald-400">3. Subscription Pricing & Payment Gateways</h3>
                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-[10px] font-bold">
                            PRO Tier Billing
                        </span>
                    </div>

                    <!-- Subscription Price Rates & Free Gating -->
                    <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-4">
                        <h4 class="text-xs font-extrabold text-sky-400 uppercase tracking-wider">💳 Subscription Price Rates &amp; Free Pick Limit</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Naira Price (₦ NGN / Month)</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-sm font-bold">₦</span>
                                    <input type="number" step="1" min="1" name="price_ngn" value="{{ old('price_ngn', $settings['price_ngn']) }}" placeholder="5000" class="w-full pl-8 pr-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-sky-400 focus:outline-none" required>
                                </div>
                                <span class="text-[11px] text-slate-500 mt-1 block">Monthly subscription fee for Nigeria (Flutterwave).</span>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-2">USD Price ($ USD / Month)</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-sm font-bold">$</span>
                                    <input type="number" step="0.01" min="0.01" name="price_usd" value="{{ old('price_usd', $settings['price_usd']) }}" placeholder="4.99" class="w-full pl-8 pr-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-sky-400 focus:outline-none" required>
                                </div>
                                <span class="text-[11px] text-slate-500 mt-1 block">Monthly fee for African USD (Flutterwave) & Global (PayPal).</span>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Free Picks Limit (Per List)</label>
                                <input type="number" min="1" max="10" step="1" name="free_pick_limit" value="{{ old('free_pick_limit', $settings['free_pick_limit'] ?? 3) }}" placeholder="3" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-sky-400 focus:outline-none" required>
                                <span class="text-[11px] text-slate-500 mt-1 block">Number of free picks visible to visitors (e.g. 3). Picks 4–10 are locked.</span>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Default Country Code</label>
                                <input type="text" name="pricing_default_country" maxlength="2" value="{{ old('pricing_default_country', $settings['pricing_default_country']) }}" placeholder="NG" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono uppercase focus:border-sky-400 focus:outline-none" required>
                                <span class="text-[11px] text-slate-500 mt-1 block">2-letter ISO code (e.g. <code>NG</code>) used if CDN geo-header is missing.</span>
                            </div>
                        </div>
                    </div>

                    <!-- PayPal Direct Email & Gateway Configuration -->
                    <div class="p-5 rounded-2xl bg-gradient-to-r from-emerald-950/40 via-slate-900/90 to-sky-950/40 border border-emerald-500/40 space-y-4">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-extrabold text-emerald-400 uppercase tracking-wider flex items-center gap-2">
                                <span>🅿️</span> PayPal Direct Email Setup (Zero Developer API Required)
                            </h4>
                            <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-[10px] font-extrabold">
                                Recommended
                            </span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-200 uppercase mb-1.5">PayPal Business Email or PayPal.me URL</label>
                            <input type="text" name="paypal_email" value="{{ old('paypal_email', $settings['paypal_email']) }}" placeholder="e.g. payments@yourdomain.com or https://paypal.me/yourusername" class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-emerald-500/50 text-white text-sm font-mono focus:border-emerald-400 focus:outline-none">
                            <span class="text-[11px] text-slate-300 mt-1.5 block">
                                💡 <strong>Super Simple:</strong> Enter your PayPal email or PayPal.me link here. When users click PayPal at checkout, they will be routed to pay directly to this PayPal account with automatic callback activation. No PayPal developer apps, Client IDs, or Webhook IDs needed.
                            </span>
                        </div>

                        <div class="pt-3 border-t border-slate-800/80">
                            <details class="text-xs text-slate-400 group">
                                <summary class="cursor-pointer font-bold text-slate-300 hover:text-emerald-400 transition-colors list-none flex items-center justify-between py-1">
                                    <span>⚙️ Optional Advanced: PayPal REST API Mode (Client ID &amp; Secret)</span>
                                    <span class="text-slate-500 group-open:rotate-180 transition-transform">▼</span>
                                </summary>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-3 pt-3 border-t border-slate-800/60">
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
                                    </div>
                                </div>
                            </details>
                        </div>
                    </div>

                    <p class="text-[11px] text-slate-500">
                        Updating the prices here dynamically applies to Flutterwave checkout calculations and verification without server redeployment.
                    </p>
                </div>

                <button type="submit" class="w-full py-4 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-extrabold text-sm shadow-xl shadow-sky-500/25 transition-all">
                    Save AI Engine Parameters & Keys &rarr;
                </button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Live Logo Preview handling
            const logoFileInput = document.getElementById('logo_file_input');
            const logoTextInput = document.getElementById('site_logo_input');
            const logoFilenameDisplay = document.getElementById('logo_filename_display');
            const liveLogoImg = document.getElementById('live_logo_img');
            const liveLogoFallback = document.getElementById('live_logo_fallback');

            function updateLogoPreview(src) {
                if (!src) {
                    liveLogoImg.style.display = 'none';
                    liveLogoFallback.style.display = 'flex';
                    return;
                }
                liveLogoImg.src = src;
                liveLogoImg.style.display = 'block';
                liveLogoFallback.style.display = 'none';
            }

            if (logoFileInput) {
                logoFileInput.addEventListener('change', function(e) {
                    if (this.files && this.files[0]) {
                        const file = this.files[0];
                        logoFilenameDisplay.textContent = 'Selected: ' + file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
                        logoFilenameDisplay.classList.add('text-amber-400');
                        const reader = new FileReader();
                        reader.onload = function(evt) {
                            updateLogoPreview(evt.target.result);
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            if (logoTextInput) {
                logoTextInput.addEventListener('input', function() {
                    updateLogoPreview(this.value.trim());
                });
            }

            // Favicon handling
            const faviconFileInput = document.getElementById('favicon_file_input');
            const faviconFilenameDisplay = document.getElementById('favicon_filename_display');
            if (faviconFileInput) {
                faviconFileInput.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        faviconFilenameDisplay.textContent = 'Selected: ' + this.files[0].name;
                        faviconFilenameDisplay.classList.add('text-amber-400');
                    }
                });
            }

            // OG Image handling
            const ogFileInput = document.getElementById('og_image_file_input');
            const ogFilenameDisplay = document.getElementById('og_filename_display');
            if (ogFileInput) {
                ogFileInput.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        ogFilenameDisplay.textContent = 'Selected: ' + this.files[0].name;
                        ogFilenameDisplay.classList.add('text-amber-400');
                    }
                });
            }

            // Telegram VIP Banner Live Preview Sync
            const tgBadgeInput = document.getElementById('telegram_badge_input');
            const tgIconInput = document.getElementById('telegram_icon_input');
            const tgHeadlineInput = document.getElementById('telegram_headline_input');
            const tgDescInput = document.getElementById('telegram_desc_input');
            const tgCtaInput = document.getElementById('telegram_cta_input');
            const tgSupportCtaInput = document.getElementById('telegram_support_cta_input');
            const tgSupportUrlInput = document.getElementById('telegram_support_url_input');

            const previewBadgeContainer = document.getElementById('preview_tg_badge_container');
            const previewBadge = document.getElementById('preview_tg_badge');
            const previewIcon = document.getElementById('preview_tg_icon');
            const previewHeadline = document.getElementById('preview_tg_headline');
            const previewDesc = document.getElementById('preview_tg_description');
            const previewCta = document.getElementById('preview_tg_cta');
            const previewSupportBtn = document.getElementById('preview_tg_support_btn');
            const previewSupportCta = document.getElementById('preview_tg_support_cta');

            if (tgBadgeInput && previewBadge) {
                tgBadgeInput.addEventListener('input', function() {
                    const val = this.value.trim();
                    previewBadge.textContent = val || 'OFFICIAL TELEGRAM COMMUNITY';
                    if (previewBadgeContainer) {
                        previewBadgeContainer.style.display = val ? 'inline-flex' : 'none';
                    }
                });
            }

            if (tgIconInput && previewIcon) {
                tgIconInput.addEventListener('input', function() {
                    previewIcon.textContent = this.value.trim() || '✈️';
                });
            }

            if (tgHeadlineInput && previewHeadline) {
                tgHeadlineInput.addEventListener('input', function() {
                    previewHeadline.textContent = this.value.trim() || 'Get Instant AI Predictions & Admin Support on Telegram';
                });
            }

            if (tgDescInput && previewDesc) {
                tgDescInput.addEventListener('input', function() {
                    previewDesc.textContent = this.value.trim() || 'Join our public channel for instant match alerts or contact live admin support anytime.';
                });
            }

            if (tgCtaInput && previewCta) {
                tgCtaInput.addEventListener('input', function() {
                    previewCta.textContent = this.value.trim() || 'Join VIP Channel';
                });
            }

            if (tgSupportCtaInput && previewSupportCta) {
                tgSupportCtaInput.addEventListener('input', function() {
                    previewSupportCta.textContent = this.value.trim() || '💬 Admin Support';
                });
            }

            if (tgSupportUrlInput && previewSupportBtn) {
                tgSupportUrlInput.addEventListener('input', function() {
                    previewSupportBtn.style.display = this.value.trim() ? 'flex' : 'none';
                });
            }
        });
    </script>
@endsection
