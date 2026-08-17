@extends('layouts.app')

@section('title', 'Team Crest Library — Admin Portal')

@section('content')
    <div class="max-w-6xl mx-auto space-y-6 py-4">
        {{-- Admin Navigation Bar --}}
        @include('admin.partials.nav')

        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm font-semibold">{{ session('success') }}</div>
        @endif

        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white">Team Crest Library</h1>
            <p class="text-xs text-slate-400">
                One record per club, reused by every fixture. Crests arrive from football-data.org on ingestion;
                override any that is missing or wrong.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-5 rounded-2xl glass-panel border border-slate-800">
                <div class="text-xs font-semibold text-slate-400 uppercase">Clubs</div>
                <div class="text-3xl font-black text-white mt-1 font-mono">{{ $counts['total'] }}</div>
            </div>
            <div class="p-5 rounded-2xl glass-panel border border-slate-800">
                <div class="text-xs font-semibold text-slate-400 uppercase">With a crest</div>
                <div class="text-3xl font-black text-emerald-400 mt-1 font-mono">{{ $counts['with_crest'] }}</div>
            </div>
            <div class="p-5 rounded-2xl glass-panel border border-slate-800">
                <div class="text-xs font-semibold text-slate-400 uppercase">Unlinked fixtures</div>
                <div class="text-3xl font-black {{ $counts['unlinked_matches'] > 0 ? 'text-amber-400' : 'text-slate-500' }} mt-1 font-mono">{{ $counts['unlinked_matches'] }}</div>
                <div class="text-[10px] text-slate-500 mt-1">Fixtures with no club record attached</div>
            </div>
        </div>

        @if($counts['unlinked_matches'] > 0)
            <div class="p-5 rounded-2xl bg-amber-500/5 border border-amber-500/30 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <p class="text-xs text-amber-200/80 max-w-2xl">
                    {{ $counts['unlinked_matches'] }} fixtures store their clubs as plain text only — these predate the crest
                    library. Linking creates a club record per name and attaches both sides. Crest images fill in on the next
                    ingestion run, which adopts these records rather than duplicating them.
                </p>
                <form action="{{ route('admin.teams.backfill') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/40 hover:bg-amber-500/20 font-extrabold text-xs whitespace-nowrap">
                        Link Existing Fixtures
                    </button>
                </form>
            </div>
        @endif

        <form method="GET" class="flex gap-2">
            <input type="text" name="q" value="{{ $search }}" placeholder="Search clubs…" class="flex-1 px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:border-sky-400 focus:outline-none">
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs">Search</button>
        </form>

        <div class="rounded-3xl glass-panel border border-slate-800 divide-y divide-slate-800/80 overflow-hidden">
            @forelse($teams as $team)
                <form action="{{ route('admin.teams.update', $team) }}" method="POST" class="p-5 flex flex-col lg:flex-row lg:items-center gap-4">
                    @csrf
                    @method('PUT')

                    <x-team-crest :team="$team" size="lg" />

                    <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Name</label>
                            <input type="text" name="name" value="{{ $team->name }}" required class="w-full px-3 py-2 rounded-lg bg-slate-900 border border-slate-700 text-white text-xs focus:border-sky-400 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Short Name</label>
                            <input type="text" name="short_name" value="{{ $team->short_name }}" class="w-full px-3 py-2 rounded-lg bg-slate-900 border border-slate-700 text-white text-xs focus:border-sky-400 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Abbrev.</label>
                            <input type="text" name="tla" value="{{ $team->tla }}" maxlength="8" class="w-full px-3 py-2 rounded-lg bg-slate-900 border border-slate-700 text-white text-xs font-mono focus:border-sky-400 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Crest Override URL</label>
                            <input type="url" name="custom_crest_url" value="{{ $team->custom_crest_url }}" placeholder="{{ $team->crest_url ?: 'no provider crest' }}" class="w-full px-3 py-2 rounded-lg bg-slate-900 border border-slate-700 text-white text-xs font-mono focus:border-sky-400 focus:outline-none">
                        </div>
                    </div>

                    <div class="flex items-center gap-3 shrink-0">
                        <span class="text-[10px] text-slate-500 font-mono whitespace-nowrap">{{ $team->home_matches_count + $team->away_matches_count }} fixtures</span>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-sky-500/10 text-sky-400 border border-sky-500/30 hover:bg-sky-500/20 text-[11px] font-bold">Save</button>
                    </div>
                </form>
            @empty
                <div class="p-10 text-center text-sm text-slate-500">
                    No clubs recorded yet. They are created automatically on the next fixture ingestion run.
                </div>
            @endforelse
        </div>

        {{ $teams->links() }}
    </div>
@endsection
