@extends('layouts.app')

@section('title', 'System Maintenance & Integration Tests — Admin Portal')

@section('content')
    <div class="max-w-5xl mx-auto py-6 space-y-6">
        @include('admin.partials.nav')

        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white">System Maintenance</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                Run the maintenance commands, check every API credential, and send a real test email —
                without shell access.
            </p>
        </div>

        {{-- Result banners. Each action redirects back with its own flash key so
             a mail result is never mistaken for a command result. --}}
        @if(session('command_output'))
            @php($r = session('command_output'))
            <div class="p-4 rounded-2xl border {{ $r['ok'] ? 'bg-emerald-500/10 border-emerald-500/30' : 'bg-rose-500/10 border-rose-500/30' }}">
                <div class="text-sm font-extrabold {{ $r['ok'] ? 'text-emerald-400' : 'text-rose-400' }}">
                    {{ $r['ok'] ? '✓' : '✕' }} {{ $r['label'] }}
                </div>
                <pre class="mt-2 text-[11px] text-slate-300 whitespace-pre-wrap font-mono overflow-x-auto">{{ $r['output'] }}</pre>
            </div>
        @endif

        @if(session('mail_result'))
            @php($r = session('mail_result'))
            <div class="p-4 rounded-2xl border text-sm {{ $r['ok'] ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : 'bg-rose-500/10 border-rose-500/30 text-rose-400' }}">
                <span class="font-extrabold">{{ $r['ok'] ? '✓ Mail sent' : '✕ Mail failed' }}</span>
                <span class="block mt-1 text-slate-300 text-xs">{{ $r['message'] }}</span>
            </div>
        @endif

        @if(session('integration_result'))
            @php($r = session('integration_result'))
            <div class="p-4 rounded-2xl border text-sm {{ $r['ok'] ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : 'bg-rose-500/10 border-rose-500/30 text-rose-400' }}">
                <span class="font-extrabold">{{ $r['ok'] ? '✓' : '✕' }} {{ $r['service'] }}</span>
                <span class="block mt-1 text-slate-300 text-xs">{{ $r['message'] }}</span>
            </div>
        @endif

        <!-- Mail status & test -->
        <div class="p-6 rounded-3xl glass-panel border border-slate-800 space-y-4">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <h2 class="text-sm font-extrabold uppercase tracking-wider text-sky-400">Email Delivery</h2>
                @if($mailer === 'log' || $mailer === 'array')
                    <span class="px-2.5 py-0.5 rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/30 text-[10px] font-bold uppercase">Mail is discarded</span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 text-[10px] font-bold uppercase">Transport: {{ $mailer }}</span>
                @endif
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                <div class="p-3 rounded-xl bg-slate-900/80 border border-slate-800">
                    <div class="text-slate-400">SMTP host</div>
                    <div class="font-mono text-slate-200 mt-0.5 break-all">{{ $mailHost ?: '—' }}</div>
                </div>
                <div class="p-3 rounded-xl bg-slate-900/80 border border-slate-800">
                    <div class="text-slate-400">From address</div>
                    <div class="font-mono text-slate-200 mt-0.5 break-all">{{ $mailFrom ?: '—' }}</div>
                </div>
                <div class="p-3 rounded-xl bg-slate-900/80 border border-slate-800">
                    <div class="text-slate-400">Configured from</div>
                    <div class="font-mono text-slate-200 mt-0.5">{{ $mailConfigured ? 'Dashboard' : '.env' }}</div>
                </div>
            </div>

            <p class="text-[11px] text-slate-500">
                SMTP credentials are set under <a href="{{ route('admin.settings') }}" class="text-sky-400 hover:underline">Settings</a>.
                The test below sends immediately rather than queueing, so a failure surfaces here instead of sitting in the jobs table.
            </p>

            <form action="{{ route('admin.system.mail-test') }}" method="POST" class="flex flex-col sm:flex-row gap-2">
                @csrf
                <input type="email" name="recipient" required value="{{ old('recipient', auth()->user()->email) }}"
                       placeholder="you@example.com"
                       class="flex-1 px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-sky-400 focus:outline-none">
                <button type="submit" class="px-5 py-3 rounded-xl bg-sky-500 hover:bg-sky-400 text-slate-950 font-black text-xs whitespace-nowrap">
                    Send test email
                </button>
            </form>
            @error('recipient')<p class="text-xs text-rose-400">{{ $message }}</p>@enderror
        </div>

        <!-- Integration tests -->
        <div class="p-6 rounded-3xl glass-panel border border-slate-800 space-y-4">
            <h2 class="text-sm font-extrabold uppercase tracking-wider text-indigo-400">API Credential Tests</h2>
            <p class="text-[11px] text-slate-500">
                Each test makes the cheapest authenticated call the provider offers. Nothing is charged, sent, or changed.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($services as $key => $label)
                    <form action="{{ route('admin.system.integration-test') }}" method="POST">
                        @csrf
                        <input type="hidden" name="service" value="{{ $key }}">
                        <button type="submit" class="w-full px-4 py-3 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-700 hover:border-indigo-500/50 text-slate-200 font-bold text-xs text-left transition-all">
                            Test {{ $label }}
                        </button>
                    </form>
                @endforeach
            </div>
        </div>

        <!-- Maintenance commands -->
        <div class="p-6 rounded-3xl glass-panel border border-slate-800 space-y-4">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <h2 class="text-sm font-extrabold uppercase tracking-wider text-amber-400">Maintenance Commands</h2>
                @if($queueDepth !== null)
                    <span class="px-2.5 py-0.5 rounded-full bg-slate-800 text-slate-300 border border-slate-700 text-[10px] font-bold uppercase">
                        {{ $queueDepth }} job{{ $queueDepth === 1 ? '' : 's' }} queued
                    </span>
                @endif
            </div>

            <div class="space-y-2">
                @foreach($commands as $key => $spec)
                    <form action="{{ route('admin.system.command') }}" method="POST"
                          class="flex items-center justify-between gap-4 p-3 rounded-xl bg-slate-900/80 border {{ $spec['danger'] ? 'border-amber-500/30' : 'border-slate-800' }}"
                          @if($spec['danger']) onsubmit="return confirm('{{ $spec['label'] }} — this changes the database. Continue?')" @endif>
                        @csrf
                        <input type="hidden" name="command" value="{{ $key }}">
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-slate-200">
                                {{ $spec['label'] }}
                                <code class="ml-1 text-[10px] text-slate-500 font-mono">{{ $spec['command'] }}</code>
                            </div>
                            <div class="text-[11px] text-slate-500 mt-0.5">{{ $spec['help'] }}</div>
                        </div>
                        <button type="submit" class="px-4 py-2 rounded-lg text-xs font-black whitespace-nowrap flex-shrink-0 {{ $spec['danger'] ? 'bg-amber-500 hover:bg-amber-400 text-slate-950' : 'bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700' }}">
                            Run
                        </button>
                    </form>
                @endforeach
            </div>

            <p class="text-[11px] text-slate-500 pt-2 border-t border-slate-800">
                Only these fixed commands can be run — there is no free-text command box, because that would be
                remote code execution behind a login form.
            </p>
        </div>
    </div>
@endsection
