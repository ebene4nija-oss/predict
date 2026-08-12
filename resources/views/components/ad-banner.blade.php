@props(['type' => 'header'])

@php
    $adEnabled = \App\Models\Setting::get('ad_enabled', '1');
    $adPartner = \App\Models\Setting::get('ad_partner_name', 'Official Betting Partner');
    $adHeadline = \App\Models\Setting::get('ad_headline', 'High Odds Multiples');
    $adDescription = \App\Models\Setting::get('ad_description', 'Get up to 200% welcome bonus on your first sports deposit.');
    $adCtaText = \App\Models\Setting::get('ad_cta_text', 'Claim Bonus');
    $adCtaUrl = \App\Models\Setting::get('ad_cta_url', '#');
@endphp

@if($adEnabled === '1')
    @auth
        @if(auth()->user()->isSubscriber())
            {{-- Ads are strictly disabled for active subscribers --}}
        @else
            <div class="my-4 p-4 rounded-xl bg-[#151A24]/60 border border-slate-800/80 text-center transition-all">
                <div class="text-[10px] tracking-widest uppercase text-slate-500 font-semibold mb-1">Sponsored Advertisement</div>
                <div class="py-3 px-4 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 rounded-lg border border-slate-700/50 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="flex items-center space-x-3 text-left">
                        <span class="p-2 bg-blue-500/10 text-blue-400 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </span>
                        <div>
                            <div class="text-sm font-semibold text-slate-200">{{ $adPartner }} • {{ $adHeadline }}</div>
                            <div class="text-xs text-slate-400">{{ $adDescription }}</div>
                        </div>
                    </div>
                    <a href="{{ $adCtaUrl }}" target="_blank" rel="noopener noreferrer" class="px-4 py-1.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-500 rounded-lg transition-colors whitespace-nowrap">
                        {{ $adCtaText }}
                    </a>
                </div>
                <div class="mt-1 text-[11px] text-slate-500">
                    Tired of ads? <a href="{{ route('subscription.pricing') }}" class="text-[#38BDF8] underline">Upgrade to PRO</a> for an ad-free experience.
                </div>
            </div>
        @endif
    @else
        <div class="my-4 p-4 rounded-xl bg-[#151A24]/60 border border-slate-800/80 text-center transition-all">
            <div class="text-[10px] tracking-widest uppercase text-slate-500 font-semibold mb-1">Sponsored Advertisement</div>
            <div class="py-3 px-4 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 rounded-lg border border-slate-700/50 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="flex items-center space-x-3 text-left">
                    <span class="p-2 bg-blue-500/10 text-blue-400 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </span>
                    <div>
                        <div class="text-sm font-semibold text-slate-200">{{ $adPartner }} • {{ $adHeadline }}</div>
                        <div class="text-xs text-slate-400">{{ $adDescription }}</div>
                    </div>
                </div>
                <a href="{{ $adCtaUrl }}" target="_blank" rel="noopener noreferrer" class="px-4 py-1.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-500 rounded-lg transition-colors whitespace-nowrap">
                    {{ $adCtaText }}
                </a>
            </div>
            <div class="mt-1 text-[11px] text-slate-500">
                Tired of ads? <a href="{{ route('subscription.pricing') }}" class="text-[#38BDF8] underline">Upgrade to PRO</a> for an ad-free experience.
            </div>
        </div>
    @endauth
@endif
