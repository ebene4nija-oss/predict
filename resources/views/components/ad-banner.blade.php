@props(['type' => 'header'])

@php
    use App\Support\AdSlot;

    /**
     * Two modes:
     *
     *   house   — the built-in sponsor banner, driven by the text settings.
     *   network — the ad network's own tag for this placement, pasted into
     *             admin. This is what actually earns money; the house banner
     *             alone is a placeholder that pays nothing.
     *
     * Either way, subscribers get nothing at all: "ad-free" is part of what
     * they paid for, and that has to mean no third-party tags either, not just
     * no visible banner.
     */
    $slot = AdSlot::for($type);
@endphp

@if ($slot->shouldRender())
    <div class="my-4 p-4 rounded-xl bg-[#151A24]/60 border border-slate-800/80 text-center transition-all">
        <div class="text-[10px] tracking-widest uppercase text-slate-500 font-semibold mb-1">Sponsored Advertisement</div>

        @if ($slot->isNetwork())
            {{-- Raw tag from the ad network. Only an admin can set this, and it
                 is deliberately not escaped — an ad tag is script by nature. --}}
            <div class="flex justify-center min-h-[90px] items-center overflow-hidden">
                {!! $slot->networkCode() !!}
            </div>
        @else
            <div class="py-3 px-4 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 rounded-lg border border-slate-700/50 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="flex items-center space-x-3 text-left">
                    <span class="p-2 bg-blue-500/10 text-blue-400 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </span>
                    <div>
                        <div class="text-sm font-semibold text-slate-200">{{ $slot->partner() }} • {{ $slot->headline() }}</div>
                        <div class="text-xs text-slate-400">{{ $slot->description() }}</div>
                    </div>
                </div>
                <a href="{{ $slot->ctaUrl() }}" target="_blank" rel="noopener noreferrer sponsored" class="px-4 py-1.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-500 rounded-lg transition-colors whitespace-nowrap">
                    {{ $slot->ctaText() }}
                </a>
            </div>
        @endif

        <div class="mt-1 text-[11px] text-slate-500">
            Tired of ads? <a href="{{ route('subscription.pricing') }}" class="text-[#38BDF8] underline">Upgrade to PRO</a> for an ad-free experience.
        </div>
    </div>
@endif
