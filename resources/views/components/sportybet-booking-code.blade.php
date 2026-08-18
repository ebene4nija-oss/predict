@props([
    'picks' => collect(),
    'customCode' => null,
    'variant' => 'full' // 'full' or 'compact'
])

@php
    $sportyService = app(\App\Services\SportyBetService::class);
    $payload = $sportyService->getTop5BookingCodePayload($picks->isNotEmpty() ? $picks : null);
    $bookingCode = $customCode ?: $payload['code'];
    $totalOdds = $payload['total_odds'];
    $loadUrl = $payload['load_url'];
    $regionName = $payload['region_name'];
    $selections = $payload['selections'];
    $uniqueId = 'sportybet_' . \Illuminate\Support\Str::random(8);
@endphp

@if($payload['enabled'] && filled($bookingCode))
    <div id="{{ $uniqueId }}" class="w-full relative rounded-2xl bg-gradient-to-r from-[#180A0E] via-[#151722] to-[#0E1522] border-2 border-red-500/40 p-4 sm:p-5 shadow-xl shadow-red-950/20 overflow-hidden my-4 group">
        <!-- Ambient Glowing Accents -->
        <div class="absolute -top-12 -right-12 w-40 h-40 bg-red-600/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-12 -left-12 w-40 h-40 bg-sky-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <!-- Left Branding & Info -->
            <div class="flex items-start sm:items-center space-x-3.5">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-red-600 to-rose-700 flex items-center justify-center text-white font-black text-sm shadow-lg shadow-red-600/30 shrink-0 border border-red-400/40">
                    <span class="tracking-tighter">SB</span>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-2 py-0.5 rounded-full bg-red-500/20 border border-red-500/30 text-red-400 text-[10px] font-black uppercase tracking-wider flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-400 animate-pulse"></span>
                            SportyBet {{ $regionName }} Slip
                        </span>
                        <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 border border-emerald-500/30 text-emerald-300 text-[10px] font-mono font-bold">
                            @ {{ number_format($totalOdds, 2) }} Total Odds
                        </span>
                        <span class="text-[10px] text-slate-400">5-Fold Multi Bet</span>
                    </div>
                    <div class="text-sm font-extrabold text-white mt-1 flex items-center gap-2">
                        <span>Top 5 AI Conviction Booking Code</span>
                    </div>
                </div>
            </div>

            <!-- Right Action Bar & Code Copy Pill -->
            <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto justify-start md:justify-end">
                <!-- Booking Code Box -->
                <div class="flex items-center rounded-xl bg-[#090C13] border border-red-500/40 px-3.5 py-2 font-mono font-black text-sm text-yellow-400 shadow-inner tracking-widest select-all">
                    <span class="text-slate-400 text-xs mr-2 font-sans font-medium uppercase select-none">Code:</span>
                    <span id="{{ $uniqueId }}_code_display" class="font-extrabold text-white">{{ $bookingCode }}</span>
                </div>

                <!-- 1-Click Copy Button -->
                <button type="button"
                        onclick="copySportyBetCode('{{ $bookingCode }}', '{{ $uniqueId }}')"
                        id="{{ $uniqueId }}_copy_btn"
                        title="Copy SportyBet Booking Code"
                        class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-red-600 via-rose-600 to-red-700 hover:from-red-500 hover:to-rose-600 text-white font-extrabold text-xs shadow-lg shadow-red-600/30 transition-all duration-200 cursor-pointer active:scale-95 whitespace-nowrap">
                    <svg id="{{ $uniqueId }}_copy_icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                    </svg>
                    <span id="{{ $uniqueId }}_copy_text">Copy SportyBet Code</span>
                </button>

                <!-- Load Betslip Direct Link -->
                <a href="{{ $loadUrl }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="inline-flex items-center space-x-1.5 px-3 py-2.5 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-700/80 text-xs font-bold transition-all whitespace-nowrap"
                   title="Open SportyBet and Load Slip">
                    <span>Load Betslip</span>
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                </a>
            </div>
        </div>

        @if($variant === 'full' && count($selections) > 0)
            <!-- Expandable / Selections Footnote -->
            <div class="mt-3 pt-3 border-t border-slate-800/80 flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-400">
                <div class="flex items-center space-x-2">
                    <span class="font-bold text-slate-300">Included Games:</span>
                    <span class="text-slate-400">
                        {{ collect($selections)->pluck('match')->implode(' • ') }}
                    </span>
                </div>
                <div class="text-[10px] text-slate-400 italic">
                    Paste code in SportyBet &rarr; Betslip &rarr; "Booking Code" to load immediately.
                </div>
            </div>
        @endif
    </div>

    @once
        <script>
            function copySportyBetCode(code, containerId) {
                if (!navigator.clipboard) {
                    // Fallback for older browsers
                    const tempInput = document.createElement('input');
                    tempInput.value = code;
                    document.body.appendChild(tempInput);
                    tempInput.select();
                    document.execCommand('copy');
                    document.body.removeChild(tempInput);
                    showSportyCopiedState(containerId);
                    return;
                }

                navigator.clipboard.writeText(code).then(function() {
                    showSportyCopiedState(containerId);
                }).catch(function(err) {
                    console.error('Could not copy booking code: ', err);
                });
            }

            function showSportyCopiedState(containerId) {
                const btn = document.getElementById(containerId + '_copy_btn');
                const text = document.getElementById(containerId + '_copy_text');
                const icon = document.getElementById(containerId + '_copy_icon');

                if (!btn || !text) return;

                const originalText = text.innerText;
                const originalBg = btn.className;

                text.innerText = '✓ Code Copied!';
                btn.classList.remove('from-red-600', 'to-red-700', 'hover:from-red-500');
                btn.classList.add('bg-emerald-600', 'text-white', 'scale-105');

                setTimeout(function() {
                    text.innerText = originalText;
                    btn.classList.remove('bg-emerald-600', 'scale-105');
                    btn.className = originalBg;
                }, 2500);
            }
        </script>
    @endonce
@endif
