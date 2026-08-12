@extends('layouts.app')

@section('title', 'Upgrade to Prophet AI PRO — Unlimited Top Picks & Expert Access')

@section('content')
    <div class="max-w-xl mx-auto py-8">
        <!-- Pricing Card -->
        <div class="p-8 rounded-3xl bg-gradient-to-br from-[#151A24] via-[#121722] to-[#0A0E17] border-2 border-sky-500/40 shadow-2xl space-y-8 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-48 h-48 bg-sky-500/10 rounded-full blur-3xl -z-0"></div>

            <!-- Popular Badge -->
            <div class="text-center relative z-10">
                <span class="px-3 py-1 rounded-full bg-gradient-to-r from-sky-500 to-blue-600 text-white font-extrabold text-[11px] uppercase tracking-wider shadow-lg shadow-sky-500/30">
                    PRO ACCESS UNLIMITED
                </span>
                <h1 class="text-3xl font-extrabold text-white mt-3">PROPHET AI Monthly PRO</h1>
                <p class="text-xs text-slate-400 mt-1">Unlock all ranked picks, AI Top 5, Expert analysis & ad-free browsing.</p>
            </div>

            <!-- Price Display -->
            <div class="text-center py-4 bg-[#0B0F17]/80 rounded-2xl border border-slate-800 relative z-10">
                <div class="flex items-baseline justify-center space-x-1">
                    <span class="text-4xl font-black text-white">₦5,000</span>
                    <span class="text-xs text-slate-400 font-semibold">/ month</span>
                </div>
                <div class="text-[11px] text-slate-500 mt-1">Or ~$9.99 USD for global accounts via PayPal</div>
            </div>

            <!-- Feature List -->
            <div class="space-y-3 relative z-10 text-xs text-slate-200">
                <div class="flex items-center space-x-3">
                    <span class="w-5 h-5 rounded-full bg-emerald-500/10 text-emerald-400 flex items-center justify-center font-bold">✓</span>
                    <span><strong>Full Access to AI's Top 5:</strong> Highest conviction cross-market picks</span>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="w-5 h-5 rounded-full bg-emerald-500/10 text-emerald-400 flex items-center justify-center font-bold">✓</span>
                    <span><strong>All Top 10 Ranked Lists:</strong> Unlocked across Win/Draw/Loss, GG & Over 2.5</span>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="w-5 h-5 rounded-full bg-emerald-500/10 text-emerald-400 flex items-center justify-center font-bold">✓</span>
                    <span><strong>Human Expert Picks:</strong> Tactical breakdowns by verified analysts</span>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="w-5 h-5 rounded-full bg-emerald-500/10 text-emerald-400 flex items-center justify-center font-bold">✓</span>
                    <span><strong>100% Ad-Free Experience:</strong> Zero display banners or promos</span>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="w-5 h-5 rounded-full bg-emerald-500/10 text-emerald-400 flex items-center justify-center font-bold">✓</span>
                    <span><strong>Failed Payment Grace Period:</strong> 7-day retry grace period before revocation</span>
                </div>
            </div>

            <!-- Payment Form -->
            <form action="{{ route('subscription.checkout') }}" method="POST" class="space-y-4 relative z-10">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Select Payment Gateway</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="p-3 rounded-xl bg-slate-900 border border-slate-700 flex items-center space-x-2 cursor-pointer hover:border-sky-400 transition-all">
                            <input type="radio" name="gateway" value="flutterwave" checked class="text-sky-400 focus:ring-0">
                            <div>
                                <div class="text-xs font-bold text-white">Flutterwave</div>
                                <div class="text-[10px] text-slate-400">Cards, Bank, Mobile Money</div>
                            </div>
                        </label>

                        <label class="p-3 rounded-xl bg-slate-900 border border-slate-700 flex items-center space-x-2 cursor-pointer hover:border-sky-400 transition-all">
                            <input type="radio" name="gateway" value="paypal" class="text-sky-400 focus:ring-0">
                            <div>
                                <div class="text-xs font-bold text-white">PayPal</div>
                                <div class="text-[10px] text-slate-400">Global & USD Payments</div>
                            </div>
                        </label>
                    </div>
                </div>

                <button type="submit" class="w-full py-4 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-extrabold text-sm shadow-xl shadow-sky-500/25 transition-all">
                    Subscribe Now & Activate Pro &rarr;
                </button>
            </form>

            <div class="text-center text-[11px] text-slate-500 relative z-10">
                Cancel anytime from your account. Native Flutterwave & PayPal subscription APIs.
            </div>
        </div>
    </div>
@endsection
