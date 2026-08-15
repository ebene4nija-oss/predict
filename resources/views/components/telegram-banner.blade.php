@props(['type' => 'banner'])

@php
    use App\Support\TelegramHandles;

    $channelUrl = TelegramHandles::channelUrl();
    $supportUrl = TelegramHandles::supportUrl();
@endphp

<div class="my-6 p-5 rounded-3xl bg-gradient-to-r from-sky-950/60 via-slate-900/90 to-indigo-950/60 border border-sky-500/30 shadow-xl relative overflow-hidden">
    <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-sky-500/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="flex flex-col md:flex-row items-center justify-between gap-4 relative z-10">
        <div class="flex items-center space-x-4">
            <div class="p-3 bg-sky-500/20 text-sky-400 rounded-2xl border border-sky-500/40 text-2xl flex-shrink-0">
                ✈️
            </div>
            <div>
                <div class="inline-flex items-center space-x-2 text-[10px] font-extrabold tracking-wider uppercase px-2.5 py-0.5 rounded-full bg-sky-500/20 text-sky-300 border border-sky-500/30 mb-1">
                    <span>OFFICIAL TELEGRAM COMMUNITY</span>
                </div>
                <h3 class="text-base sm:text-lg font-black text-white">Get Instant AI Predictions & Admin Support on Telegram</h3>
                <p class="text-xs text-slate-300">Join our public channel for instant match alerts or contact live admin support anytime.</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto justify-start md:justify-end">
            <a href="{{ $channelUrl }}" target="_blank" rel="noopener noreferrer" class="flex-1 md:flex-none px-4 py-2.5 rounded-xl bg-sky-500 hover:bg-sky-400 text-slate-950 font-black text-xs transition-all shadow-lg shadow-sky-500/25 flex items-center justify-center space-x-1.5 whitespace-nowrap">
                <span>Join VIP Channel</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
            {{-- No support contact configured yet: a button linking to nowhere
                 is worse than no button. --}}
            @if($supportUrl !== '')
                <a href="{{ $supportUrl }}" target="_blank" rel="noopener noreferrer" class="flex-1 md:flex-none px-4 py-2.5 rounded-xl bg-slate-800/90 hover:bg-slate-700 text-slate-200 font-bold text-xs border border-slate-700 transition-all flex items-center justify-center space-x-1.5 whitespace-nowrap">
                    <span>💬 Admin Support</span>
                </a>
            @endif
        </div>
    </div>
</div>
