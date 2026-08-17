@extends('layouts.app')

@section('title', 'Expert Profile & Pick Manager — Admin Portal')

@section('content')
    <div class="max-w-6xl mx-auto space-y-8 py-4">
        <!-- Admin Navigation Bar -->
        @include('admin.partials.nav')

        <!-- Flash Message Banner -->
        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-semibold flex items-center space-x-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Page Title -->
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white">Expert Profile & Pick Manager</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">Manage verified tipster profiles and publish manual expert match predictions.</p>
        </div>

        <!-- SECTION A: EXPERT PROFILES -->
        <div class="space-y-6">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h2 class="text-xl font-bold text-white flex items-center space-x-2">
                    <span>A. Expert Profiles</span>
                    <span class="text-xs font-mono text-slate-500">({{ $experts->count() }} profiles)</span>
                </h2>
            </div>

            <!-- Existing Expert Profiles List -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @forelse($experts as $expert)
                    <div class="p-5 rounded-2xl glass-panel border border-slate-800 space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center space-x-3">
                                @if($expert->photo_path)
                                    <img src="{{ $expert->photo_path }}" alt="{{ $expert->name }}" class="w-12 h-12 rounded-full object-cover border border-amber-500/40">
                                @else
                                    <div class="w-12 h-12 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/30 flex items-center justify-center font-bold text-base">
                                        {{ strtoupper(substr($expert->name, 0, 2)) }}
                                    </div>
                                @endif
                                <div>
                                    <h3 class="font-extrabold text-white text-base">{{ $expert->name }}</h3>
                                    <span class="text-[11px] text-amber-400 font-bold uppercase tracking-wider">
                                        {{ $expert->picks_count ?? $expert->picks->count() }} Published Picks
                                    </span>
                                </div>
                            </div>

                            <!-- Delete Form -->
                            <form action="{{ route('admin.experts.destroy', $expert) }}" method="POST" onsubmit="return confirm('Delete this expert profile?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 text-xs transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>

                        @if($expert->bio)
                            <p class="text-xs text-slate-400 italic bg-slate-900/60 p-2.5 rounded-xl border border-slate-800">"{{ $expert->bio }}"</p>
                        @endif

                        <!-- Inline Edit Form -->
                        <form action="{{ route('admin.experts.update', $expert) }}" method="POST" class="space-y-3 pt-2 border-t border-slate-800/80">
                            @csrf
                            @method('PUT')
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Name</label>
                                    <input type="text" name="name" value="{{ old('name', $expert->name) }}" required class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs focus:border-amber-400 focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Photo URL</label>
                                    <input type="url" name="photo_path" value="{{ old('photo_path', $expert->photo_path) }}" placeholder="https://..." class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs font-mono focus:border-amber-400 focus:outline-none">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Bio</label>
                                <input type="text" name="bio" value="{{ old('bio', $expert->bio) }}" placeholder="Expert bio / background..." class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs focus:border-amber-400 focus:outline-none">
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" class="px-4 py-1.5 rounded-xl bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/40 text-xs font-bold transition-all">
                                    Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                @empty
                    <div class="col-span-full p-6 rounded-2xl glass-panel text-center text-slate-400 text-xs">
                        No expert profiles registered yet.
                    </div>
                @endforelse
            </div>

            <!-- Add New Expert Form Card -->
            <div class="p-6 rounded-2xl glass-panel border border-slate-800 space-y-4">
                <h3 class="text-base font-extrabold text-white flex items-center space-x-2">
                    <span class="text-amber-400">+</span>
                    <span>Add New Expert Profile</span>
                </h3>

                <form action="{{ route('admin.experts.store') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Expert Name *</label>
                        <input type="text" name="name" required placeholder="e.g. Alex Ferguson" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs focus:border-amber-400 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Photo URL</label>
                        <input type="url" name="photo_path" placeholder="https://images.unsplash.com/..." class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs font-mono focus:border-amber-400 focus:outline-none">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Bio / Analyst Credentials</label>
                        <input type="text" name="bio" placeholder="Former Premier League tactical analyst with 10+ years experience..." class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs focus:border-amber-400 focus:outline-none">
                    </div>

                    <div class="sm:col-span-3 flex justify-end">
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs transition-all shadow-lg shadow-amber-500/20">
                            Create Expert Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- SECTION B: EXPERT PICKS -->
        <div class="space-y-6 pt-6 border-t border-slate-800">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h2 class="text-xl font-bold text-white flex items-center space-x-2">
                    <span>B. Expert Picks</span>
                </h2>
            </div>

            <!-- Submit New Expert Pick Form -->
            @php
                $upcomingMatches = \App\Models\GameMatch::where('kickoff_at', '>', now())->orderBy('kickoff_at', 'asc')->get();
            @endphp

            <div class="p-6 rounded-2xl glass-panel border border-slate-800 space-y-4">
                <h3 class="text-base font-extrabold text-white flex items-center space-x-2">
                    <span class="text-indigo-400">🎯</span>
                    <span>Submit New Expert Pick</span>
                </h3>

                <form action="{{ route('admin.experts.picks.store') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                    @csrf

                    <div class="sm:col-span-4">
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Select Expert *</label>
                        <select name="expert_id" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs focus:border-indigo-400 focus:outline-none">
                            <option value="">-- Choose Expert --</option>
                            @foreach($experts as $expert)
                                <option value="{{ $expert->id }}">{{ $expert->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-4">
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Select Match *</label>
                        <select name="match_id" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs focus:border-indigo-400 focus:outline-none">
                            <option value="">-- Choose Upcoming Match --</option>
                            @foreach($upcomingMatches as $matchItem)
                                <option value="{{ $matchItem->id }}">
                                    {{ $matchItem->home_team }} vs {{ $matchItem->away_team }} ({{ $matchItem->kickoff_at?->format('M d, H:i') }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-4">
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Market *</label>
                        <select name="market" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs focus:border-indigo-400 focus:outline-none">
                            @foreach(\App\Support\MarketRegistry::generated() as $key => $definition)
                                <option value="{{ $key }}">{{ $definition->label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-6">
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Pick Selection *</label>
                        <input type="text" name="pick" required placeholder="e.g. Arsenal Win, GG, or Over 2.5" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs focus:border-indigo-400 focus:outline-none">
                    </div>

                    <div class="sm:col-span-6">
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Confidence Rating (0.50 - 0.99) *</label>
                        <input type="number" step="0.01" min="0.50" max="0.99" name="confidence" value="0.75" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs font-mono focus:border-indigo-400 focus:outline-none">
                    </div>

                    <div class="sm:col-span-12">
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Expert Analysis / Rationale</label>
                        <textarea name="rationale" rows="3" placeholder="Provide detailed rationale supporting this pick..." class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs focus:border-indigo-400 focus:outline-none"></textarea>
                    </div>

                    <div class="sm:col-span-12 flex justify-end">
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-extrabold text-xs transition-all shadow-lg shadow-indigo-500/25">
                            Submit New Expert Pick
                        </button>
                    </div>
                </form>
            </div>

            <!-- Existing Expert Picks Table -->
            <div class="rounded-2xl glass-panel border border-slate-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="bg-slate-900/80 border-b border-slate-800 text-[10px] uppercase font-bold text-slate-400">
                            <tr>
                                <th class="px-4 py-3.5">Expert</th>
                                <th class="px-4 py-3.5">Match</th>
                                <th class="px-4 py-3.5">Market</th>
                                <th class="px-4 py-3.5">Pick</th>
                                <th class="px-4 py-3.5">Confidence</th>
                                <th class="px-4 py-3.5">Rationale</th>
                                <th class="px-4 py-3.5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            @forelse($picks as $pick)
                                <tr class="hover:bg-slate-800/30 transition-colors">
                                    <td class="px-4 py-3 font-bold text-amber-400 whitespace-nowrap">
                                        {{ $pick->expert->name ?? 'Deleted Expert' }}
                                    </td>
                                    <td class="px-4 py-3 font-bold text-white whitespace-nowrap">
                                        {{ $pick->match->home_team ?? 'N/A' }} vs {{ $pick->match->away_team ?? 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-400 uppercase font-mono text-[10px] whitespace-nowrap">
                                        {{ uppercase_mkt($pick->market) }}
                                    </td>
                                    <td class="px-4 py-3 font-extrabold text-sky-400 whitespace-nowrap">
                                        {{ $pick->pick }}
                                    </td>
                                    <td class="px-4 py-3 font-mono font-bold text-emerald-400 whitespace-nowrap">
                                        {{ round($pick->confidence * 100) }}%
                                    </td>
                                    <td class="px-4 py-3 text-slate-400 max-w-xs truncate">
                                        {{ $pick->rationale ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <form action="{{ route('admin.experts.picks.destroy', $pick) }}" method="POST" onsubmit="return confirm('Delete this expert pick?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3 py-1 rounded-lg bg-rose-600/80 hover:bg-rose-600 text-white text-xs font-semibold transition-colors">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-slate-400">
                                        No expert picks submitted yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div class="pt-4">
                {{ $picks->links() }}
            </div>
        </div>
    </div>
@endsection
