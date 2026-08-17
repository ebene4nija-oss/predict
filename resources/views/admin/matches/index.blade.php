@extends('layouts.app')

@section('title', 'Match & Fixture Manager — Guaranteed Correct Admin')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <!-- Admin Navigation Bar -->
    @include('admin.partials.nav')

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white">Match & Fixture Manager</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">Manage football fixtures, run AI models, and settle final scores.</p>
        </div>
        <a href="{{ route('admin.matches.create') }}" class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white text-xs font-extrabold shadow-lg shadow-sky-500/25 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            <span>+ Create New Fixture</span>
        </a>
    </div>

    <!-- Flash Message Display -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-emerald-200 font-bold">&times;</button>
        </div>
    @endif

    <!-- Matches Listing Table -->
    <div class="rounded-3xl glass-panel border border-slate-800 overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300 border-collapse">
                <thead>
                    <tr class="bg-slate-900/60 border-b border-slate-800 text-xs uppercase font-semibold text-slate-400">
                        <th class="py-4 px-4 sm:px-6">ID</th>
                        <th class="py-4 px-4 sm:px-6">Home Team</th>
                        <th class="py-4 px-4 sm:px-6">Away Team</th>
                        <th class="py-4 px-4 sm:px-6">League</th>
                        <th class="py-4 px-4 sm:px-6">Kickoff</th>
                        <th class="py-4 px-4 sm:px-6">Status</th>
                        <th class="py-4 px-4 sm:px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($matches as $match)
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="py-4 px-4 sm:px-6 font-mono text-xs text-slate-400">#{{ $match->id }}</td>
                            <td class="py-4 px-4 sm:px-6 font-bold text-white">{{ $match->home_team }}</td>
                            <td class="py-4 px-4 sm:px-6 font-bold text-white">{{ $match->away_team }}</td>
                            <td class="py-4 px-4 sm:px-6">
                                <span class="px-2.5 py-1 rounded-lg bg-slate-800 text-slate-300 border border-slate-700 text-xs font-medium">
                                    {{ $match->league }}
                                </span>
                            </td>
                            <td class="py-4 px-4 sm:px-6 text-xs text-slate-300 font-mono">
                                {{ $match->kickoff_at ? $match->kickoff_at->format('M d, Y H:i') : 'TBD' }}
                            </td>
                            <td class="py-4 px-4 sm:px-6">
                                @if($match->result)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        Settled ({{ $match->result->home_score }} - {{ $match->result->away_score }})
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-sky-500/10 text-sky-400 border border-sky-500/20">
                                        Upcoming
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-4 sm:px-6 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('admin.matches.edit', $match) }}" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-sky-400 text-xs font-semibold border border-slate-700 transition-all">
                                        Edit
                                    </a>
                                    <a href="{{ route('admin.matches.settle', $match) }}" class="px-3 py-1.5 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 text-xs font-semibold border border-emerald-500/30 transition-all">
                                        Settle
                                    </a>
                                    <form action="{{ route('admin.matches.destroy', $match) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this fixture?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 text-xs font-semibold border border-rose-500/30 transition-all">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500 text-sm">
                                No match fixtures found. Click "+ Create New Fixture" to add your first match.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($matches->hasPages())
            <div class="p-4 border-t border-slate-800">
                {{ $matches->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
