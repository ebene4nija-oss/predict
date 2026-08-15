@props(['eyebrow', 'heading', 'operator', 'address' => '', 'updatedAt'])

{{--
    Shared chrome for the legal pages: same heading, same "last updated" line,
    same prose rules, so the three documents cannot drift apart visually.
--}}
<div class="max-w-3xl mx-auto py-4 space-y-8">
    <div class="space-y-3">
        <div class="inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-full bg-slate-500/10 border border-slate-500/20 text-slate-300 text-xs font-semibold">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span>{{ strtoupper($eyebrow) }}</span>
        </div>
        <h1 class="text-3xl sm:text-4xl font-black text-white">{{ $heading }}</h1>
        <p class="text-xs text-slate-500">
            Last updated {{ $updatedAt }} &middot; Operated by {{ $operator }}@if($address), {{ $address }}@endif
        </p>
    </div>

    <div class="p-6 sm:p-8 rounded-3xl glass-panel space-y-6 text-sm leading-relaxed text-slate-300
                [&_h2]:text-lg [&_h2]:font-bold [&_h2]:text-white [&_h2]:pt-2
                [&_h3]:text-sm [&_h3]:font-bold [&_h3]:text-slate-100
                [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:space-y-1.5
                [&_a]:text-[#38BDF8] [&_a]:underline">
        {{ $slot }}
    </div>

    <div class="flex flex-wrap gap-4 text-xs text-slate-500">
        <a href="{{ route('legal.terms') }}" class="hover:text-slate-300">Terms of Service</a>
        <a href="{{ route('legal.privacy') }}" class="hover:text-slate-300">Privacy Policy</a>
        <a href="{{ route('legal.refunds') }}" class="hover:text-slate-300">Refund &amp; Cancellation Policy</a>
    </div>
</div>
