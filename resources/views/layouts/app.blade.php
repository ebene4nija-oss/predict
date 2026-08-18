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
        $customSiteLogo = \App\Models\Setting::get('site_logo', file_exists(public_path('images/logo.png')) ? asset('images/logo.png') : null);
        $customFavicon = \App\Models\Setting::get('site_favicon', file_exists(public_path('favicon.ico')) ? asset('favicon.ico') : null);
        $customOgImage = \App\Models\Setting::get('site_og_image', file_exists(public_path('images/og-banner.png')) ? asset('images/og-banner.png') : null);
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

    <title>@yield('title', 'Guaranteed Correct Score Tips — #1 AI Football Predictions & Exact Score Tips')</title>
    <meta name="description" content="@yield('meta_description', $seoMetaDesc)">
    <meta name="keywords" content="@yield('meta_keywords', $seoMetaKeywords)">
    <link rel="canonical" href="@yield('canonical', url()->current())" />

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="@yield('og_type', 'website')" />
    <meta property="og:site_name" content="Guaranteed Correct Score Tips" />
    <meta property="og:url" content="@yield('canonical', url()->current())" />
    <meta property="og:title" content="@yield('title', 'Guaranteed Correct Score Tips — #1 AI Football Predictions & Exact Score Tips')" />
    <meta property="og:description" content="@yield('meta_description', $seoMetaDesc)" />
    <meta property="og:image" content="@yield('og_image', $customOgImage ?: asset('images/og-banner.png'))" />

    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:url" content="@yield('canonical', url()->current())" />
    <meta name="twitter:title" content="@yield('title', 'Guaranteed Correct Score Tips — #1 AI Football Predictions & Exact Score Tips')" />
    <meta name="twitter:description" content="@yield('meta_description', $seoMetaDesc)" />
    <meta name="twitter:image" content="@yield('og_image', $customOgImage ?: asset('images/og-banner.png'))" />

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

    <!-- Schema.org Global Organization & WebSite JSON-LD -->
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                '@id' => route('home') . '#organization',
                'name' => 'Guaranteed Correct Score Tips',
                'alternateName' => ['Guaranteed Correct Score', 'Guaranteed Correct', 'GuaranteedCorrectScoreTips'],
                'url' => route('home'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $customSiteLogo ?: asset('images/og-banner.png'),
                ],
                'description' => 'Guaranteed Correct Score Tips provides high-accuracy exact score football predictions, Poisson expected goals (xG) matrix modeling, and audited handicapper tips.',
            ],
            [
                '@type' => 'WebSite',
                '@id' => route('home') . '#website',
                'url' => route('home'),
                'name' => 'Guaranteed Correct Score Tips',
                'alternateName' => 'Guaranteed Correct Score Tips',
                'publisher' => [
                    '@id' => route('home') . '#organization',
                ],
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    @yield('schema_json_ld')
</head>
<body class="min-h-screen flex flex-col antialiased bg-[#0B0F17] text-slate-100 selection:bg-sky-500 selection:text-white">
    <!-- Main Header -->
    <header class="sticky top-0 z-50 bg-[#0B0F17] border-b border-slate-800/80 shadow-[0_4px_30px_rgba(0,0,0,0.8)] isolate">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 sm:h-20 flex items-center justify-between">
            <!-- Brand Logo Lockup -->
            <a href="{{ route('home') }}" class="flex items-center space-x-3 group py-1.5 focus:outline-none" aria-label="Guaranteed Correct Home">
                @if($customSiteLogo)
                    <div class="relative flex items-center">
                        <img src="{{ $customSiteLogo }}" class="h-11 sm:h-14 w-auto max-w-[200px] sm:max-w-[280px] object-contain transition-all duration-300 group-hover:scale-[1.03] filter drop-shadow-[0_0_16px_rgba(56,189,248,0.35)]" alt="Guaranteed Correct Synthetic Precision">
                    </div>
                @else
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 via-sky-500 to-emerald-400 p-[1px] shadow-lg shadow-sky-500/20 group-hover:scale-105 transition-transform">
                        <div class="w-full h-full bg-[#0B0F17] rounded-[11px] flex items-center justify-center">
                            <svg class="w-5 h-5 text-[#38BDF8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                    </div>
                    <div class="flex flex-col">
                        <div class="flex items-center space-x-1.5">
                            <span class="font-extrabold text-base sm:text-lg tracking-tight text-white uppercase group-hover:text-sky-200 transition-colors">
                                GUARANTEED <span class="text-transparent bg-clip-text bg-gradient-to-r from-sky-400 to-emerald-400">CORRECT</span>
                            </span>
                            <span class="hidden sm:inline-block text-[9px] uppercase tracking-wider font-extrabold px-1.5 py-0.5 rounded-md bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
                                ✓ AI
                            </span>
                        </div>
                        <span class="text-[9px] tracking-widest text-slate-400 font-bold uppercase -mt-0.5">Synthetic Precision</span>
                    </div>
                @endif
            </a>

            <!-- Navigation Links -->
            <nav class="hidden md:flex items-center space-x-1 lg:space-x-3 text-sm font-medium">
                <a href="{{ route('home') }}" class="px-3 py-2 rounded-lg transition-all duration-200 {{ request()->routeIs('home') ? 'text-[#38BDF8] bg-sky-500/10 font-bold border border-sky-500/25 shadow-[0_0_10px_rgba(56,189,248,0.2)]' : 'text-slate-300 hover:text-white hover:bg-slate-800/50' }}">
                    Home
                </a>
                <a href="{{ route('top.picks') }}" class="px-3 py-2 rounded-lg transition-all duration-200 {{ request()->routeIs('top.picks') ? 'text-[#38BDF8] bg-sky-500/10 font-bold border border-sky-500/25 shadow-[0_0_10px_rgba(56,189,248,0.2)]' : 'text-slate-300 hover:text-white hover:bg-slate-800/50' }}">
                    Top Picks
                    <span class="ml-1.5 text-[10px] font-extrabold px-1.5 py-0.5 rounded bg-sky-500/20 text-sky-400 border border-sky-500/30">PRO</span>
                </a>
                <a href="{{ route('matches.index') }}" class="px-3 py-2 rounded-lg transition-all duration-200 {{ request()->routeIs('matches.*') ? 'text-[#38BDF8] bg-sky-500/10 font-bold border border-sky-500/25 shadow-[0_0_10px_rgba(56,189,248,0.2)]' : 'text-slate-300 hover:text-white hover:bg-slate-800/50' }}">
                    Match Previews
                </a>
                <a href="{{ route('expert.picks') }}" class="px-3 py-2 rounded-lg transition-all duration-200 {{ request()->routeIs('expert.picks') ? 'text-[#F5A623] bg-[#F5A623]/10 font-bold border border-[#F5A623]/25 shadow-[0_0_10px_rgba(245,166,35,0.2)]' : 'text-slate-300 hover:text-[#F5A623] hover:bg-slate-800/50' }}">
                    Expert Picks
                </a>
                <a href="{{ route('track-record') }}" class="px-3 py-2 rounded-lg transition-all duration-200 {{ request()->routeIs('track-record') ? 'text-[#22C55E] bg-emerald-500/10 font-bold border border-emerald-500/25 shadow-[0_0_10px_rgba(34,197,94,0.2)]' : 'text-slate-300 hover:text-[#22C55E] hover:bg-slate-800/50' }}">
                    Track Record
                </a>
                <a href="{{ route('blog.index') }}" class="px-3 py-2 rounded-lg transition-all duration-200 {{ request()->routeIs('blog.*') ? 'text-[#38BDF8] bg-sky-500/10 font-bold border border-sky-500/25' : 'text-slate-300 hover:text-white hover:bg-slate-800/50' }}">
                    News
                </a>
                <a href="{{ route('how-ai-works') }}" class="px-3 py-2 rounded-lg transition-all duration-200 {{ request()->routeIs('how-ai-works') ? 'text-[#38BDF8] bg-sky-500/10 font-bold border border-sky-500/25' : 'text-slate-300 hover:text-white hover:bg-slate-800/50' }}">
                    How AI Works
                </a>
                <a href="{{ route('home') }}#sportybet-slip" class="px-2.5 py-1.5 rounded-lg text-rose-400 hover:text-white bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/25 transition-all text-xs font-bold flex items-center gap-1.5 shadow-sm shadow-rose-900/30" title="Quick SportyBet Top 5 Slip">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400 animate-pulse"></span>
                    <span>SportyBet Slip</span>
                </a>
            </nav>

            <!-- User Auth Controls -->
            <div class="flex items-center space-x-2 sm:space-x-3">
                @auth
                    @if(auth()->user()->isSubscriber())
                        <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-extrabold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 shadow-[0_0_12px_rgba(34,197,94,0.25)]">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            PRO ACTIVE
                        </span>
                    @else
                        <a href="{{ route('subscription.pricing') }}" class="px-4 py-2 text-xs font-extrabold rounded-xl bg-gradient-to-r from-sky-500 via-blue-600 to-emerald-500 hover:from-sky-400 hover:to-emerald-400 text-white shadow-lg shadow-sky-500/25 border border-sky-400/30 transition-all active:scale-95">
                            Upgrade PRO
                        </a>
                    @endif

                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="px-3 py-1.5 text-xs font-bold rounded-xl bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 hover:bg-indigo-500/30 transition-all flex items-center gap-1">
                            <span>⚙️</span> <span class="hidden sm:inline">Admin</span>
                        </a>
                    @endif

                    @if(auth()->user()->isExpert())
                        <a href="{{ route('expert.submit') }}" class="px-3 py-1.5 text-xs font-semibold rounded-xl bg-[#F5A623]/15 text-[#F5A623] border border-[#F5A623]/30 hover:bg-[#F5A623]/25 transition-all">
                            + Submit Pick
                        </a>
                    @endif

                    @if(auth()->user()->avatarUrl())
                        <a href="{{ route('account') }}" class="p-1 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-slate-200 border border-slate-700/60 transition-colors flex items-center justify-center shrink-0" title="My Account ({{ auth()->user()->name }})">
                            <img src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()->name }}" class="w-7 h-7 rounded-lg object-cover">
                        </a>
                    @else
                        <a href="{{ route('account') }}" class="p-2 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-slate-200 border border-slate-700/60 transition-colors shrink-0" title="My Account">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="text-xs sm:text-sm font-semibold text-slate-300 hover:text-white px-3 py-2 transition-colors">Log in</a>
                    <a href="{{ route('register') }}" class="px-3.5 sm:px-4 py-2 text-xs font-extrabold rounded-xl bg-gradient-to-r from-sky-500 via-blue-600 to-emerald-500 hover:from-sky-400 hover:to-emerald-400 text-white shadow-lg shadow-sky-500/25 border border-sky-400/30 transition-all active:scale-95">
                        Get Started
                    </a>
                @endauth
            </div>
        </div>
        <!-- Hairline gradient glow border -->
        <div class="h-[1px] w-full bg-gradient-to-r from-transparent via-sky-500/40 via-emerald-500/30 to-transparent"></div>
    </header>

    <!-- Mobile Subnav Bar -->
    <div class="md:hidden bg-[#0D121B]/95 backdrop-blur-md border-b border-slate-800/80 py-2.5 px-4 flex items-center justify-between text-xs overflow-x-auto gap-3 no-scrollbar">
        <a href="{{ route('home') }}" class="whitespace-nowrap px-2.5 py-1 rounded-lg {{ request()->routeIs('home') ? 'text-[#38BDF8] bg-sky-500/10 font-bold border border-sky-500/20' : 'text-slate-400' }}">Home</a>
        <a href="{{ route('top.picks') }}" class="whitespace-nowrap px-2.5 py-1 rounded-lg {{ request()->routeIs('top.picks') ? 'text-[#38BDF8] bg-sky-500/10 font-bold border border-sky-500/20' : 'text-slate-400' }}">Top Picks</a>
        <a href="{{ route('matches.index') }}" class="whitespace-nowrap px-2.5 py-1 rounded-lg {{ request()->routeIs('matches.*') ? 'text-[#38BDF8] bg-sky-500/10 font-bold border border-sky-500/20' : 'text-slate-400' }}">Previews</a>
        <a href="{{ route('expert.picks') }}" class="whitespace-nowrap px-2.5 py-1 rounded-lg {{ request()->routeIs('expert.picks') ? 'text-[#F5A623] bg-[#F5A623]/10 font-bold border border-[#F5A623]/20' : 'text-slate-400' }}">Expert Picks</a>
        <a href="{{ route('track-record') }}" class="whitespace-nowrap px-2.5 py-1 rounded-lg {{ request()->routeIs('track-record') ? 'text-[#22C55E] bg-emerald-500/10 font-bold border border-emerald-500/20' : 'text-slate-400' }}">Track Record</a>
        <a href="{{ route('blog.index') }}" class="whitespace-nowrap px-2.5 py-1 rounded-lg {{ request()->routeIs('blog.*') ? 'text-[#38BDF8] bg-sky-500/10 font-bold border border-sky-500/20' : 'text-slate-400' }}">News</a>
        <a href="{{ route('subscription.pricing') }}" class="whitespace-nowrap px-2.5 py-1 rounded-lg text-sky-400 font-bold bg-sky-500/10 border border-sky-500/30">Pricing</a>
        <a href="{{ route('home') }}#sportybet-slip" class="whitespace-nowrap px-2.5 py-1 rounded-lg text-rose-400 font-bold bg-rose-500/10 border border-rose-500/30 flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-rose-400 animate-pulse"></span> SportyBet</a>
    </div>

    <!-- Alert Banners -->
    @if(session('success'))
        <div class="max-w-7xl mx-auto px-4 mt-4">
            <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm flex items-center justify-between shadow-lg shadow-emerald-500/5">
                <div class="flex items-center space-x-2">
                    <span class="text-base">✓</span>
                    <span class="font-semibold">{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-emerald-300 font-bold text-lg">&times;</button>
            </div>
        </div>
    @endif

    @if(session('warning'))
        <div class="max-w-7xl mx-auto px-4 mt-4">
            <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-400 text-sm flex items-center justify-between shadow-lg shadow-amber-500/5">
                <div class="flex items-center space-x-2">
                    <span class="text-base">⚠️</span>
                    <span class="font-semibold">{{ session('warning') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-amber-400 hover:text-amber-300 font-bold text-lg">&times;</button>
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @yield('content')

        <x-ad-banner type="footer" />
    </main>

    <!-- Footer with Compliance Disclaimer -->
    <footer class="mt-16 bg-[#080B10] border-t border-slate-800/80 text-slate-400 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6 pb-8 border-b border-slate-800/60">
                <a href="{{ route('home') }}" class="flex items-center space-x-3 group">
                    @if($customSiteLogo)
                        <img src="{{ $customSiteLogo }}" class="h-10 sm:h-12 w-auto max-w-[220px] object-contain filter drop-shadow-[0_0_12px_rgba(56,189,248,0.25)] group-hover:scale-105 transition-transform" alt="Guaranteed Correct Logo">
                    @else
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-600 to-sky-400 flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <span class="font-extrabold text-slate-200 tracking-tight text-base uppercase">GUARANTEED CORRECT <span class="text-sky-400">SCORE TIPS</span></span>
                    @endif
                </a>
                <div class="flex flex-wrap items-center justify-center gap-6 text-xs text-slate-400">
                    <a href="{{ route('home') }}" class="hover:text-sky-400 transition-colors">Home</a>
                    <a href="{{ route('top.picks') }}" class="hover:text-sky-400 transition-colors">Top Picks</a>
                    <a href="{{ route('matches.index') }}" class="hover:text-sky-400 transition-colors">Match Previews</a>
                    <a href="{{ route('expert.picks') }}" class="hover:text-[#F5A623] transition-colors">Expert Picks</a>
                    <a href="{{ route('track-record') }}" class="hover:text-emerald-400 transition-colors">Track Record</a>
                    <a href="{{ route('blog.index') }}" class="hover:text-sky-400 transition-colors">News</a>
                    <a href="{{ route('how-ai-works') }}" class="hover:text-sky-400 transition-colors">How AI Works</a>
                    <a href="{{ route('subscription.pricing') }}" class="hover:text-sky-400 transition-colors">Pricing</a>
                    <a href="{{ route('legal.terms') }}" class="hover:text-slate-200 transition-colors">Terms</a>
                    <a href="{{ route('legal.privacy') }}" class="hover:text-slate-200 transition-colors">Privacy</a>
                    <a href="{{ route('legal.refunds') }}" class="hover:text-slate-200 transition-colors">Refunds</a>
                </div>
            </div>

            <!-- Mandatory Disclaimer & Compliance Notice -->
            <div class="mt-8 space-y-3 text-center md:text-left text-xs text-slate-500">
                <div class="flex items-center justify-center md:justify-start space-x-2 text-amber-400 font-semibold">
                    <span class="px-2 py-0.5 rounded-md bg-amber-500/10 border border-amber-500/25 text-[10px] font-extrabold">18+</span>
                    <span class="tracking-wide">PREDICTIONS, NOT GUARANTEES</span>
                </div>
                <p class="leading-relaxed">
                    Guaranteed Correct Score Tips provides statistical match modeling, expected-goals probability calculations, and expert opinion for informational and entertainment purposes only. Past performance does not guarantee future success. Please gamble responsibly.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-between text-[11px] text-slate-600 pt-2 gap-2">
                    <p>&copy; {{ date('Y') }} GUARANTEED CORRECT SCORE TIPS — Synthetic Precision Platform. All rights reserved.</p>
                    <p class="text-slate-500 font-medium">Billed securely via Flutterwave &amp; PayPal</p>
                </div>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
