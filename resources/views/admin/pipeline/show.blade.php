@extends('layouts.app')

@section('title', 'Pipeline Run #' . $run->id . ' — Live Log')

@section('content')
    <div class="max-w-6xl mx-auto space-y-6 py-4"
         data-lines-url="{{ route('admin.pipeline.lines', $run) }}"
         data-drain-url="{{ route('admin.pipeline.drain', $run) }}"
         data-finished="{{ $run->isFinished() ? '1' : '0' }}"
         id="pipeline-panel">

        {{-- Admin Navigation Bar --}}
        @include('admin.partials.nav')

        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm font-semibold flex items-center justify-between">
                <span>{{ session('success') }}</span>
                <span class="text-xs text-emerald-300">Live monitor active</span>
            </div>
        @endif

        <!-- Run Header -->
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/30 text-xs font-bold mb-2">
                    <span class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span>
                    <span>AI PIPELINE · RUN #{{ $run->id }}</span>
                </div>
                <h1 class="text-3xl font-extrabold text-white flex items-center gap-3">
                    Live Pipeline Console
                    <span id="status-badge" class="px-2.5 py-1 rounded-full text-[11px] font-black uppercase tracking-wider border">…</span>
                </h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-1">
                    Triggered by <span class="text-slate-200 font-semibold">{{ $run->user?->name ?? 'system' }}</span> at {{ $run->created_at->format('d M Y, H:i:s') }}
                    @if($run->days) · lead time <span class="text-sky-400 font-mono">{{ $run->days }}d</span> @endif
                    · <span id="run-step" class="text-slate-300 font-semibold"></span>
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('admin.dashboard') }}" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs border border-slate-700 transition-all">
                    ← Dashboard
                </a>
                <form action="{{ route('admin.pipeline.run') }}" method="POST" class="flex items-center gap-2">
                    @csrf
                    <select name="days" class="px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-slate-300 text-xs font-bold focus:ring-1 focus:ring-sky-500 focus:border-sky-500">
                        <option value="3" {{ ($run->days ?? 7) == 3 ? 'selected' : '' }}>3 Days</option>
                        <option value="7" {{ ($run->days ?? 7) == 7 ? 'selected' : '' }}>7 Days</option>
                        <option value="14" {{ ($run->days ?? 7) == 14 ? 'selected' : '' }}>14 Days</option>
                    </select>
                    <button type="submit" id="rerun-button" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-extrabold text-xs shadow-lg shadow-sky-500/25 transition-all flex items-center space-x-1.5 disabled:opacity-40 disabled:cursor-not-allowed">
                        <span>↻ Run Pipeline</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Waiting for worker notice with Instant Drain Button -->
        <div id="worker-warning" class="hidden p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-sm">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div>
                    <div class="font-bold flex items-center gap-2">
                        <span class="text-base">⏳</span>
                        <span>Queued for Worker</span>
                    </div>
                    <p class="text-xs text-amber-200/80 mt-1">
                        Pending in queue: <span id="queue-depth" class="font-mono font-bold">{{ $queueDepth ?? '—' }}</span>.
                        If a queue daemon is not running locally, click to process immediately.
                    </p>
                </div>
                <button type="button" id="drain-btn" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs transition-all shadow-md flex items-center gap-1.5 whitespace-nowrap">
                    <span id="drain-spinner" class="hidden animate-spin">⚙️</span>
                    <span>⚡ Process Queue Now</span>
                </button>
            </div>
        </div>

        <!-- Stalled notice -->
        <div id="stall-warning" class="hidden p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-sm">
            <div class="font-bold mb-1">⚠ No output for {{ \App\Models\PipelineRun::STALL_MINUTES }} minutes.</div>
            <p class="text-xs text-rose-200/80">
                The worker process may have timed out or stopped. Check
                <code class="text-rose-200 font-mono">storage/logs/laravel.log</code> and the <code class="text-rose-200 font-mono">failed_jobs</code> table, then run the pipeline again.
            </p>
        </div>

        <!-- Error Summary Box (if failed) -->
        <div id="error-summary-box" class="hidden p-4 rounded-2xl bg-rose-500/15 border border-rose-500/40 text-rose-300 space-y-2">
            <div class="font-extrabold text-sm flex items-center gap-2">
                <span>❌ Pipeline Failed</span>
            </div>
            <div id="error-summary-msg" class="text-xs font-mono bg-rose-950/60 p-3 rounded-xl border border-rose-800/50 text-rose-200 overflow-x-auto"></div>
            <div class="text-[11px] text-rose-300/80 flex items-center gap-2">
                <span>Tip: Verify your API keys and tokens in <a href="{{ route('admin.settings') }}" class="underline font-bold text-white hover:text-rose-200">Admin → AI Settings</a>.</span>
            </div>
        </div>

        @if($runsInline)
            <div class="p-4 rounded-xl bg-slate-800/60 border border-slate-700 text-slate-300 text-xs flex items-center gap-2">
                <span class="text-sky-400">ℹ️</span>
                <span>Queue driver is <strong class="font-mono text-white">sync</strong>, so runs execute inline with synchronous streaming.</span>
            </div>
        @endif

        <!-- Terminal Console -->
        <div class="rounded-3xl bg-[#0B0F16] border-2 border-slate-800 overflow-hidden shadow-2xl">
            <!-- Console Topbar -->
            <div class="flex flex-wrap items-center justify-between px-4 py-2.5 bg-[#151A24] border-b border-slate-800 gap-3">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-rose-500/80"></span>
                    <span class="w-3 h-3 rounded-full bg-amber-500/80"></span>
                    <span class="w-3 h-3 rounded-full bg-emerald-500/80"></span>
                    <span class="ml-2 text-xs font-mono text-slate-300 font-bold">guaranteed-correct@engine:~/pipeline</span>
                </div>

                <!-- Controls: Filter, Elapsed, Auto-Scroll, Copy -->
                <div class="flex items-center flex-wrap gap-3">
                    <div class="relative">
                        <input type="text" id="log-filter" placeholder="Filter output…" class="px-2.5 py-1 text-[11px] rounded-lg bg-slate-900 border border-slate-700 text-slate-300 font-mono focus:outline-none focus:ring-1 focus:ring-sky-500 w-28 sm:w-40">
                    </div>

                    <span class="text-[11px] font-mono text-slate-400">
                        elapsed: <span id="elapsed" class="text-slate-200 font-bold">0s</span>
                    </span>

                    <label class="flex items-center gap-1.5 text-[11px] font-mono text-slate-400 cursor-pointer select-none">
                        <input type="checkbox" id="follow-toggle" checked class="w-3.5 h-3.5 rounded bg-slate-900 border-slate-600 text-sky-500 focus:ring-0">
                        <span>auto-scroll</span>
                    </label>

                    <button type="button" id="copy-log-btn" class="px-2.5 py-1 text-[11px] font-mono rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition-all flex items-center gap-1">
                        <span>📋</span>
                        <span id="copy-log-text">Copy</span>
                    </button>
                </div>
            </div>

            <!-- Console Stream Window -->
            <div id="console" class="h-[55vh] overflow-y-auto px-4 py-3 font-mono text-[12px] leading-relaxed space-y-1 select-text scroll-smooth">
                <div id="console-empty" class="text-slate-500 flex items-center gap-2">
                    <svg class="w-4 h-4 animate-spin text-slate-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span>Connecting to live pipeline transcript…</span>
                </div>
            </div>

            <!-- Console Footer -->
            <div class="px-4 py-2.5 bg-[#151A24] border-t border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span id="line-count" class="text-[11px] font-mono text-slate-400">0 lines</span>
                    <span id="filter-match-count" class="text-[11px] font-mono text-sky-400 hidden"></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping" id="live-ping"></span>
                    <span id="poll-state" class="text-[11px] font-mono text-slate-400">polling…</span>
                </div>
            </div>
        </div>

        <noscript>
            <div class="rounded-2xl bg-[#0B0F16] border border-slate-800 p-4 font-mono text-[12px] text-slate-300 space-y-1">
                @foreach($run->lines()->orderBy('id')->get() as $line)
                    <div>[{{ optional($line->created_at)->format('H:i:s') }}] {{ $line->message }}</div>
                @endforeach
                <div class="text-slate-500">Reload the page for more output — live streaming needs JavaScript.</div>
            </div>
        </noscript>

        <!-- Recent runs history -->
        <div class="p-5 rounded-3xl glass-panel border border-slate-800">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-extrabold text-white uppercase tracking-wider">Run History</h2>
                <span class="text-xs text-slate-400">Showing last {{ count($recent) }} runs</span>
            </div>
            <div class="space-y-1.5">
                @foreach($recent as $item)
                    <a href="{{ route('admin.pipeline.show', $item) }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl {{ $item->is($run) ? 'bg-sky-500/10 border border-sky-500/30' : 'bg-slate-900/60 border border-slate-800 hover:border-slate-700' }} text-xs transition-all">
                        <div class="flex items-center gap-3">
                            <span class="font-mono text-slate-300 font-bold">#{{ $item->id }}</span>
                            <span class="text-slate-400">{{ $item->created_at->format('d M H:i:s') }}</span>
                            <span class="text-slate-500">by {{ $item->user?->name ?? 'system' }}</span>
                            @if($item->days)
                                <span class="px-1.5 py-0.5 rounded bg-slate-800 text-[10px] text-slate-400 font-mono">{{ $item->days }}d</span>
                            @endif
                        </div>
                        <span class="font-bold uppercase tracking-wider text-[10px] px-2 py-0.5 rounded-full
                            @class([
                                'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' => $item->status === \App\Models\PipelineRun::STATUS_COMPLETED,
                                'bg-rose-500/10 text-rose-400 border border-rose-500/30' => $item->status === \App\Models\PipelineRun::STATUS_FAILED,
                                'bg-sky-500/10 text-sky-400 border border-sky-500/30 animate-pulse' => $item->status === \App\Models\PipelineRun::STATUS_RUNNING,
                                'bg-amber-500/10 text-amber-400 border border-amber-500/30' => $item->status === \App\Models\PipelineRun::STATUS_QUEUED,
                            ])">
                            {{ $item->status }}@if($item->durationSeconds() !== null && $item->isFinished()) · {{ $item->durationSeconds() }}s @endif
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <script>
        (function () {
            const panel = document.getElementById('pipeline-panel');
            const consoleEl = document.getElementById('console');
            const emptyEl = document.getElementById('console-empty');
            const badge = document.getElementById('status-badge');
            const stepEl = document.getElementById('run-step');
            const elapsedEl = document.getElementById('elapsed');
            const lineCountEl = document.getElementById('line-count');
            const pollStateEl = document.getElementById('poll-state');
            const followToggle = document.getElementById('follow-toggle');
            const workerWarning = document.getElementById('worker-warning');
            const stallWarning = document.getElementById('stall-warning');
            const queueDepthEl = document.getElementById('queue-depth');
            const rerunButton = document.getElementById('rerun-button');
            const drainBtn = document.getElementById('drain-btn');
            const drainSpinner = document.getElementById('drain-spinner');
            const copyLogBtn = document.getElementById('copy-log-btn');
            const copyLogText = document.getElementById('copy-log-text');
            const logFilter = document.getElementById('log-filter');
            const filterMatchCount = document.getElementById('filter-match-count');
            const errorSummaryBox = document.getElementById('error-summary-box');
            const errorSummaryMsg = document.getElementById('error-summary-msg');
            const livePing = document.getElementById('live-ping');

            const linesUrl = panel.dataset.linesUrl;
            const drainUrl = panel.dataset.drainUrl;
            const csrfToken = '{{ csrf_token() }}';
            const POLL_MS = 1000;

            let lastId = 0;
            let lineCount = 0;
            let stopped = false;
            let failures = 0;
            let queuedSince = Date.now();
            let startedAt = null;
            const rawLines = [];

            const LEVEL_CLASS = {
                step: 'text-sky-300 font-bold mt-2.5 pt-2 border-t border-slate-800/80 flex items-start gap-2',
                info: 'text-slate-300 flex items-start gap-2',
                success: 'text-emerald-400 font-semibold flex items-start gap-2',
                warning: 'text-amber-400 flex items-start gap-2',
                error: 'text-rose-400 font-bold flex items-start gap-2',
            };

            const STATUS_CLASS = {
                queued: 'bg-amber-500/10 text-amber-400 border-amber-500/30',
                running: 'bg-sky-500/10 text-sky-400 border-sky-500/30 animate-pulse',
                completed: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
                failed: 'bg-rose-500/10 text-rose-400 border-rose-500/30',
            };

            function atBottom() {
                return consoleEl.scrollHeight - consoleEl.scrollTop - consoleEl.clientHeight < 40;
            }

            function appendLine(line) {
                if (emptyEl) { emptyEl.remove(); }

                rawLines.push(line);
                const wasAtBottom = atBottom();

                const row = document.createElement('div');
                row.className = LEVEL_CLASS[line.level] || LEVEL_CLASS.info;
                row.dataset.lineId = line.id;
                row.dataset.text = (line.message || '').toLowerCase();

                const stamp = document.createElement('span');
                stamp.className = 'text-slate-600 select-none font-mono shrink-0';
                stamp.textContent = '[' + (line.at || '--:--:--') + ']';
                row.appendChild(stamp);

                const msgSpan = document.createElement('span');
                msgSpan.className = 'break-words';
                msgSpan.textContent = line.message;
                row.appendChild(msgSpan);

                consoleEl.appendChild(row);
                lineCount += 1;

                applyFilter();

                if (followToggle.checked && wasAtBottom) {
                    consoleEl.scrollTop = consoleEl.scrollHeight;
                }
            }

            function applyFilter() {
                const query = (logFilter.value || '').trim().toLowerCase();
                let matches = 0;
                const rows = consoleEl.querySelectorAll('[data-line-id]');

                rows.forEach(function (row) {
                    if (!query || (row.dataset.text && row.dataset.text.includes(query))) {
                        row.style.display = '';
                        matches++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                if (query) {
                    filterMatchCount.textContent = matches + ' / ' + lineCount + ' matching';
                    filterMatchCount.classList.remove('hidden');
                } else {
                    filterMatchCount.classList.add('hidden');
                }
            }

            if (logFilter) {
                logFilter.addEventListener('input', applyFilter);
            }

            function renderStatus(data) {
                badge.textContent = data.status;
                badge.className = 'px-2.5 py-1 rounded-full text-[11px] font-black uppercase tracking-wider border ' +
                    (STATUS_CLASS[data.status] || STATUS_CLASS.queued);

                stepEl.textContent = data.step ? data.step : (data.finished ? (data.status === 'completed' ? 'Finished successfully' : 'Failed') : 'Processing');
                lineCountEl.textContent = lineCount + (lineCount === 1 ? ' line' : ' lines');

                if (data.duration !== null && data.duration !== undefined) {
                    startedAt = Date.now() - (data.duration * 1000);
                }

                if (queueDepthEl && data.queue_depth !== null) {
                    queueDepthEl.textContent = data.queue_depth;
                }

                // Show worker warning if queued for > 3s
                const waitingTooLong = data.status === 'queued' && (Date.now() - queuedSince) > 3000;
                workerWarning.classList.toggle('hidden', !waitingTooLong);
                stallWarning.classList.toggle('hidden', !data.stalled);

                if (data.status === 'failed' && data.error) {
                    errorSummaryMsg.textContent = data.error;
                    errorSummaryBox.classList.remove('hidden');
                } else {
                    errorSummaryBox.classList.add('hidden');
                }

                if (rerunButton) {
                    rerunButton.disabled = !data.finished && data.status !== 'queued';
                }
            }

            function tickElapsed() {
                if (startedAt === null) { return; }
                const seconds = Math.max(0, Math.round((Date.now() - startedAt) / 1000));
                elapsedEl.textContent = seconds < 60
                    ? seconds + 's'
                    : Math.floor(seconds / 60) + 'm ' + (seconds % 60) + 's';
            }

            async function poll() {
                if (stopped) { return; }

                try {
                    const response = await fetch(linesUrl + '?after=' + lastId, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });

                    if (!response.ok) { throw new Error('HTTP ' + response.status); }

                    const data = await response.json();
                    failures = 0;

                    data.lines.forEach(function (line) {
                        appendLine(line);
                        lastId = Math.max(lastId, line.id);
                    });

                    renderStatus(data);

                    if (data.finished) {
                        stopped = true;
                        if (livePing) { livePing.classList.remove('animate-ping'); }
                        if (startedAt !== null) { tickElapsed(); }
                        pollStateEl.textContent = data.status === 'completed' ? 'completed' : 'failed';
                        if (emptyEl && lineCount === 0) { emptyEl.textContent = 'No output recorded for this run.'; }
                        return;
                    }

                    pollStateEl.textContent = 'live streaming…';
                } catch (error) {
                    failures += 1;
                    pollStateEl.textContent = 'reconnecting (' + failures + ')';

                    if (failures >= 15) {
                        stopped = true;
                        pollStateEl.textContent = 'connection paused — reload to resume';
                        return;
                    }
                }

                setTimeout(poll, POLL_MS + (failures * 800));
            }

            // On-demand Queue Processing
            if (drainBtn) {
                drainBtn.addEventListener('click', async function () {
                    drainBtn.disabled = true;
                    if (drainSpinner) { drainSpinner.classList.remove('hidden'); }

                    try {
                        const res = await fetch(drainUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        const resData = await res.json();
                        if (resData.ok) {
                            workerWarning.classList.add('hidden');
                        }
                    } catch (e) {
                        console.error('Drain error:', e);
                    } finally {
                        drainBtn.disabled = false;
                        if (drainSpinner) { drainSpinner.classList.add('hidden'); }
                    }
                });
            }

            // Copy Log Functionality
            if (copyLogBtn) {
                copyLogBtn.addEventListener('click', function () {
                    const text = rawLines.map(function (l) {
                        return '[' + (l.at || '') + '] ' + (l.message || '');
                    }).join('\n');

                    navigator.clipboard.writeText(text).then(function () {
                        copyLogText.textContent = 'Copied!';
                        setTimeout(function () { copyLogText.textContent = 'Copy'; }, 2000);
                    });
                });
            }

            setInterval(tickElapsed, 1000);
            poll();
        })();
    </script>
@endsection
