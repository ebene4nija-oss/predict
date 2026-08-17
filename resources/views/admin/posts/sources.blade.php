@extends('layouts.app')

@section('title', 'Newswire Sources — Admin Portal')

@section('content')
    <div class="max-w-6xl mx-auto space-y-6 py-4">
        {{-- Admin Navigation Bar --}}
        @include('admin.partials.nav')

        {{-- Written out rather than interpolated: Tailwind's compiler only
             sees class names that appear literally in the source. --}}
        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm font-semibold">{{ session('success') }}</div>
        @endif

        @if(session('warning'))
            <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-400 text-sm font-semibold">{{ session('warning') }}</div>
        @endif

        @if($errors->any())
            <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm space-y-1">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <a href="{{ route('admin.posts.index') }}" class="text-xs font-bold text-slate-400 hover:text-sky-400">&larr; Back to Newsroom</a>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white mt-1">Newswire Sources</h1>
                <p class="text-xs text-slate-400">RSS and Atom feeds the newsroom watches. New stories become original articles that credit the original.</p>
            </div>

            <form action="{{ route('admin.sources.poll-all') }}" method="POST">
                @csrf
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-extrabold text-xs shadow-lg shadow-sky-500/25 transition-all whitespace-nowrap">
                    ⟳ Poll All Feeds Now
                </button>
            </form>
        </div>

        {{-- How this behaves, stated where the decision is made --}}
        <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 text-[11px] text-slate-400 leading-relaxed space-y-2">
            <p>
                <span class="font-bold text-slate-200">Feeds are a prompt, not a source of copy.</span>
                The model is given a headline and summary and asked to write our own article about what the story means for
                the fixtures and probabilities we publish. It is instructed not to paraphrase the original, not to quote it,
                and to attribute claims to the publication by name. Each article links back to the original report.
            </p>
            <p>
                Every story is written once — deduplicated on the feed's own entry id — and
                @if(\App\Models\Setting::get('ai_posts_autopublish', '0') === '1')
                    <span class="text-amber-400 font-semibold">auto-publish is on, so articles go live without review.</span>
                @else
                    arrives as a draft for you to read before publishing.
                @endif
                Only use feeds you are entitled to monitor, and check the publisher's terms.
            </p>
            @unless($aiConfigured)
                <p class="text-amber-400 font-semibold">No Claude API key is set, so polling will not write anything yet.</p>
            @endunless
        </div>

        {{-- Existing feeds --}}
        <div class="space-y-4">
            @forelse($sources as $source)
                <div class="p-5 rounded-3xl glass-panel border border-slate-800 space-y-4">
                    <form action="{{ route('admin.sources.update', $source) }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">
                            <div class="lg:col-span-3">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Publication</label>
                                <input type="text" name="name" value="{{ $source->name }}" required class="w-full px-3 py-2 rounded-lg bg-slate-900 border border-slate-700 text-white text-xs focus:border-sky-400 focus:outline-none">
                            </div>

                            <div class="lg:col-span-5">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Feed URL</label>
                                <input type="url" name="feed_url" value="{{ $source->feed_url }}" required class="w-full px-3 py-2 rounded-lg bg-slate-900 border border-slate-700 text-white text-xs font-mono focus:border-sky-400 focus:outline-none">
                            </div>

                            <div class="lg:col-span-2">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Files Under</label>
                                <select name="category" class="w-full px-3 py-2 rounded-lg bg-slate-900 border border-slate-700 text-white text-xs focus:border-sky-400 focus:outline-none">
                                    @foreach(\App\Models\Post::CATEGORIES as $value => $label)
                                        <option value="{{ $value }}" @selected($source->category === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="lg:col-span-1">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Max/Run</label>
                                <input type="number" name="max_per_run" value="{{ $source->max_per_run }}" min="1" max="10" required class="w-full px-3 py-2 rounded-lg bg-slate-900 border border-slate-700 text-white text-xs font-mono focus:border-sky-400 focus:outline-none">
                            </div>

                            <div class="lg:col-span-1">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Max Age</label>
                                <input type="number" name="max_age_hours" value="{{ $source->max_age_hours }}" min="1" max="336" required class="w-full px-3 py-2 rounded-lg bg-slate-900 border border-slate-700 text-white text-xs font-mono focus:border-sky-400 focus:outline-none">
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-4 flex-wrap">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" @checked($source->is_active) class="w-4 h-4 rounded bg-slate-900 border-slate-600 text-emerald-500 focus:ring-emerald-500">
                                <span class="text-[11px] font-bold text-slate-300 uppercase">Active</span>
                            </label>

                            <div class="text-[10px] text-slate-500 font-mono">
                                {{ $source->posts_count }} articles ·
                                {{ $source->last_fetched_at ? 'polled '.$source->last_fetched_at->diffForHumans() : 'never polled' }}
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="submit" class="px-4 py-2 rounded-lg bg-sky-500/10 text-sky-400 border border-sky-500/30 hover:bg-sky-500/20 text-[11px] font-bold">Save</button>
                            </div>
                        </div>
                    </form>

                    @if($source->last_error)
                        <div class="p-3 rounded-xl bg-rose-500/5 border border-rose-500/30 text-[11px] text-rose-300">
                            Last poll failed: {{ $source->last_error }}
                        </div>
                    @endif

                    <div class="flex items-center gap-2 pt-3 border-t border-slate-800/80">
                        <form action="{{ route('admin.sources.preview', $source) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] font-bold">Test Read</button>
                        </form>

                        <form action="{{ route('admin.sources.poll', $source) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/20 text-[11px] font-bold">Poll &amp; Write</button>
                        </form>

                        <form action="{{ route('admin.sources.destroy', $source) }}" method="POST" onsubmit="return confirm('Remove this feed? Articles already written are kept.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-rose-500/10 text-rose-400 border border-rose-500/30 hover:bg-rose-500/20 text-[11px] font-bold">Remove</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="p-10 rounded-3xl glass-panel border border-slate-800 text-center text-sm text-slate-500">
                    No feeds yet. Add one below to start the newswire.
                </div>
            @endforelse
        </div>

        {{-- Add a feed --}}
        <div class="p-6 sm:p-8 rounded-3xl glass-panel border border-slate-800 space-y-5">
            <h2 class="text-sm font-extrabold uppercase tracking-wider text-emerald-400">Add a Feed</h2>

            <form action="{{ route('admin.sources.store') }}" method="POST" class="space-y-4">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Publication Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. BBC Sport Football" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:border-emerald-400 focus:outline-none">
                        <span class="text-[11px] text-slate-500 mt-1 block">Shown to readers as the credit on every article from this feed.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Feed URL</label>
                        <input type="url" name="feed_url" value="{{ old('feed_url') }}" required placeholder="https://feeds.example.com/football.xml" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-emerald-400 focus:outline-none">
                        <span class="text-[11px] text-slate-500 mt-1 block">RSS 2.0 or Atom. Use Test Read afterwards to confirm it parses.</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-2">File Articles Under</label>
                        <select name="category" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:border-emerald-400 focus:outline-none">
                            @foreach(\App\Models\Post::CATEGORIES as $value => $label)
                                <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Max Articles Per Poll</label>
                        <input type="number" name="max_per_run" value="{{ old('max_per_run', 2) }}" min="1" max="10" required class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-emerald-400 focus:outline-none">
                        <span class="text-[11px] text-slate-500 mt-1 block">One model call each. Keep it low on busy feeds.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Ignore Stories Older Than</label>
                        <input type="number" name="max_age_hours" value="{{ old('max_age_hours', 48) }}" min="1" max="336" required class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-emerald-400 focus:outline-none">
                        <span class="text-[11px] text-slate-500 mt-1 block">Hours. Stops a new feed backfilling old news.</span>
                    </div>
                </div>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 rounded bg-slate-900 border-slate-600 text-emerald-500 focus:ring-emerald-500">
                    <span class="text-xs font-bold text-slate-300 uppercase">Start polling immediately</span>
                </label>

                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white font-extrabold text-xs shadow-lg shadow-emerald-500/25 transition-all">
                        Add Feed
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
