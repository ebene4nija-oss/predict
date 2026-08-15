<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @php
        $ga4MeasurementId = \App\Models\Setting::get('ga4_measurement_id', env('GA4_MEASUREMENT_ID'));
        $searchConsoleCode = \App\Models\Setting::get('search_console_verification_code', env('SEARCH_CONSOLE_CODE'));
        $seoMetaDesc = \App\Models\Setting::get('seo_meta_description', 'Transparent, data-driven football predictions powered by expected-goals Poisson modeling and verified expert analysis.');
        $seoMetaKeywords = \App\Models\Setting::get('seo_meta_keywords', 'football predictions, AI betting tips, Poisson xG model, football picks, sports analytics');
        $customSiteLogo = \App\Models\Setting::get('site_logo');
        $customFavicon = \App\Models\Setting::get('site_favicon');
        $customOgImage = \App\Models\Setting::get('site_og_image', asset('images/og-banner.png'));
    @endphp

    @if($customFavicon)
        <link rel="icon" href="{{ $customFavicon }}" />
    @endif

    @if($searchConsoleCode)
        <meta name="google-site-verification" content="{{ $searchConsoleCode }}" />
    @endif

    {{-- Ad network loader. Free tier only: a subscriber paying for "ad-free"
         should not be loading the network's script, let alone be tracked by it. --}}
    @php $adHead = \App\Support\AdSlot::for('header'); @endphp
    @if($adHead->shouldRender() && $adHead->isNetwork())
        {!! \App\Models\Setting::get('ad_network_head', '') !!}
    @endif

    <title>@yield('title', 'GUARANTEED CORRECT — Football Predictions & Expert Picks')</title>
    <meta name="description" content="@yield('meta_description', $seoMetaDesc)">
    <meta name="keywords" content="@yield('meta_keywords', $seoMetaKeywords)">
    <link rel="canonical" href="{{ url()->current() }}" />

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ url()->current() }}" />
    <meta property="og:title" content="@yield('title', 'GUARANTEED CORRECT — Football Predictions & Expert Picks')" />
    <meta property="og:description" content="@yield('meta_description', $seoMetaDesc)" />
    <meta property="og:image" content="@yield('og_image', $customOgImage)" />

    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:url" content="{{ url()->current() }}" />
    <meta name="twitter:title" content="@yield('title', 'GUARANTEED CORRECT — Football Predictions & Expert Picks')" />
    <meta name="twitter:description" content="@yield('meta_description', $seoMetaDesc)" />
    <meta name="twitter:image" content="@yield('og_image', $customOgImage)" />

    @if($ga4MeasurementId)
        <!-- Google Analytics 4 (GA4) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4MeasurementId }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ $ga4MeasurementId }}');
        </script>
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Compiled by Vite. The previous CDN build of Tailwind compiled every
         page in the browser on every visit, which the Tailwind project
         explicitly does not support in production. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @yield('schema_json_ld')
</head>
<body class="min-h-screen flex flex-col antialiased">
    <!-- Main Header -->
    <header class="sticky top-0 z-50 glass-panel border-b border-slate-800/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <!-- Brand Logo -->
            <a href="{{ route('home') }}" class="flex items-center space-x-3 group">
                @if($customSiteLogo)
                    <img src="{{ $customSiteLogo }}" class="h-9 object-contain max-w-[160px] group-hover:scale-105 transition-transform" alt="Guaranteed Correct Logo">
                @else
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-600 to-sky-400 flex items-center justify-center shadow-lg shadow-sky-500/20 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                @endif
                <div>
                    <span class="font-extrabold text-lg tracking-tight text-white">GUARANTEED <span class="text-[#38BDF8]">CORRECT</span></span>
                    <span class="hidden sm:inline-block ml-2 text-[10px] uppercase tracking-widest font-semibold px-2 py-0.5 rounded-full bg-blue-500/10 text-[#38BDF8] border border-blue-500/20">xG V2.4</span>
                </div>
            </a>

            <!-- Navigation Links -->
            <nav class="hidden md:flex items-center space-x-6 text-sm font-medium">
                <a href="{{ route('home') }}" class="transition-colors hover:text-[#38BDF8] {{ request()->routeIs('home') ? 'text-[#38BDF8] font-semibold' : 'text-slate-300' }}">Home</a>
                <a href="{{ route('top.picks') }}" class="transition-colors hover:text-[#38BDF8] {{ request()->routeIs('top.picks') ? 'text-[#38BDF8] font-semibold' : 'text-slate-300' }}">
                    Top Picks
                    <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded bg-sky-500/20 text-sky-400">PRO</span>
                </a>
                <a href="{{ route('expert.picks') }}" class="transition-colors hover:text-[#F5A623] {{ request()->routeIs('expert.picks') ? 'text-[#F5A623] font-semibold' : 'text-slate-300' }}">
                    Expert Picks
                </a>
                <a href="{{ route('track-record') }}" class="transition-colors hover:text-[#38BDF8] {{ request()->routeIs('track-record') ? 'text-[#38BDF8] font-semibold' : 'text-slate-300' }}">Track Record</a>
                <a href="{{ route('how-ai-works') }}" class="transition-colors hover:text-[#38BDF8] {{ request()->routeIs('how-ai-works') ? 'text-[#38BDF8] font-semibold' : 'text-slate-300' }}">How AI Works</a>
            </nav>

            <!-- User Auth Controls -->
            <div class="flex items-center space-x-3">
                @auth
                    @if(auth()->user()->isSubscriber())
                        <span class="hidden sm:inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            PRO ACTIVE
                        </span>
                    @else
                        <a href="{{ route('subscription.pricing') }}" class="px-3.5 py-1.5 text-xs font-bold rounded-lg bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white shadow-lg shadow-sky-500/25 transition-all">
                            Upgrade PRO
                        </a>
                    @endif

                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="px-3 py-1.5 text-xs font-bold rounded-lg bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 hover:bg-indigo-500/30 transition-all">
                            ⚙️ Admin Portal
                        </a>
                    @endif

                    @if(auth()->user()->isExpert())
                        <a href="{{ route('expert.submit') }}" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-[#F5A623]/10 text-[#F5A623] border border-[#F5A623]/30 hover:bg-[#F5A623]/20 transition-all">
                            + Submit Pick
                        </a>
                    @endif

                    <a href="{{ route('account') }}" class="p-2 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-slate-200 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium text-slate-300 hover:text-white px-3 py-1.5 transition-colors">Log in</a>
                    <a href="{{ route('register') }}" class="px-4 py-1.5 text-xs font-bold rounded-lg bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white shadow-lg shadow-sky-500/25 transition-all">
                        Get Started
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Mobile Subnav Bar -->
    <div class="md:hidden glass-panel border-b border-slate-800/60 py-2 px-4 flex items-center justify-between text-xs overflow-x-auto gap-4">
        <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'text-[#38BDF8] font-bold' : 'text-slate-400' }}">Home</a>
        <a href="{{ route('top.picks') }}" class="{{ request()->routeIs('top.picks') ? 'text-[#38BDF8] font-bold' : 'text-slate-400' }}">Top Picks</a>
        <a href="{{ route('expert.picks') }}" class="{{ request()->routeIs('expert.picks') ? 'text-[#F5A623] font-bold' : 'text-slate-400' }}">Expert Picks</a>
        <a href="{{ route('track-record') }}" class="{{ request()->routeIs('track-record') ? 'text-[#38BDF8] font-bold' : 'text-slate-400' }}">Track Record</a>
        <a href="{{ route('subscription.pricing') }}" class="text-sky-400 font-semibold">Pricing</a>
    </div>

    <!-- Alert Banners -->
    @if(session('success'))
        <div class="max-w-7xl mx-auto px-4 mt-4">
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm flex items-center justify-between">
                <span>{{ session('success') }}</span>
                <button onclick="this.parentElement.remove()" class="text-emerald-400 font-bold">&times;</button>
            </div>
        </div>
    @endif

    @if(session('warning'))
        <div class="max-w-7xl mx-auto px-4 mt-4">
            <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-400 text-sm flex items-center justify-between">
                <span>{{ session('warning') }}</span>
                <button onclick="this.parentElement.remove()" class="text-amber-400 font-bold">&times;</button>
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @yield('content')

        <x-ad-banner type="footer" />
    </main>

    <!-- Footer with Compliance Disclaimer -->
    <footer class="mt-16 bg-[#080B10] border-t border-slate-800/80 text-slate-400 py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6 pb-8 border-b border-slate-800/60">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-blue-600 to-sky-400 flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <span class="font-bold text-slate-200">GUARANTEED CORRECT</span>
                </div>
                <div class="flex flex-wrap items-center gap-6 text-xs text-slate-400">
                    <a href="{{ route('home') }}" class="hover:text-slate-200">Home</a>
                    <a href="{{ route('top.picks') }}" class="hover:text-slate-200">Top Picks</a>
                    <a href="{{ route('expert.picks') }}" class="hover:text-slate-200">Expert Picks</a>
                    <a href="{{ route('track-record') }}" class="hover:text-slate-200">Track Record</a>
                    <a href="{{ route('how-ai-works') }}" class="hover:text-slate-200">How AI Works</a>
                    <a href="{{ route('subscription.pricing') }}" class="hover:text-slate-200">Pricing</a>
                    <a href="{{ route('legal.terms') }}" class="hover:text-slate-200">Terms</a>
                    <a href="{{ route('legal.privacy') }}" class="hover:text-slate-200">Privacy</a>
                    <a href="{{ route('legal.refunds') }}" class="hover:text-slate-200">Refunds</a>
                </div>
            </div>

            <!-- Mandatory Disclaimer & Compliance Notice -->
            <div class="mt-8 space-y-3 text-center md:text-left text-xs text-slate-500">
                <div class="flex items-center justify-center md:justify-start space-x-2 text-amber-400/90 font-semibold">
                    <span class="px-1.5 py-0.5 rounded bg-amber-500/10 border border-amber-500/20 text-[10px]">18+</span>
                    <span>PREDICTIONS, NOT GUARANTEES</span>
                </div>
                <p>
                    Guaranteed Correct provides statistical match modeling, expected-goals probability calculations, and expert opinion for informational and entertainment purposes only. Past performance does not guarantee future success. Please gamble responsibly.
                </p>
                <p class="text-[11px] text-slate-600">
                    &copy; {{ date('Y') }} GUARANTEED CORRECT Platform. All rights reserved. Billed via Flutterwave & PayPal native payment gateways.
                </p>
            </div>
        </div>
    </footer>
</body>
</html>
