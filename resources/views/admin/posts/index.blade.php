@extends('layouts.app')

@section('title', 'News & Announcements — Admin Portal')

@section('content')
    <div class="max-w-6xl mx-auto space-y-6 py-4">
        {{-- Admin Navigation Bar --}}
        @include('admin.partials.nav')

        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm font-semibold">{{ session('success') }}</div>
        @endif

        @if(session('warning'))
            <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-400 text-sm font-semibold">{{ session('warning') }}</div>
        @endif

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white">Newsroom</h1>
                <p class="text-xs text-slate-400">News, announcements and analysis — written by staff or by the model, always labelled.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.sources.index') }}" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs border border-slate-700 transition-all text-center whitespace-nowrap">
                    📡 Newswire Sources
                </a>
                <a href="{{ route('admin.posts.create') }}" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-extrabold text-xs shadow-lg shadow-sky-500/25 transition-all text-center whitespace-nowrap">
                    ✍️ Write Article
                </a>
            </div>
        </div>

        {{-- Counters --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            @foreach([
                ['Total', $counts['total'], 'text-white'],
                ['Published', $counts['published'], 'text-emerald-400'],
                ['Drafts', $counts['draft'], 'text-amber-400'],
                ['AI-written', $counts['ai'], 'text-[#38BDF8]'],
            ] as [$label, $value, $tone])
                <div class="p-5 rounded-2xl glass-panel border border-slate-800">
                    <div class="text-xs font-semibold text-slate-400 uppercase">{{ $label }}</div>
                    <div class="text-3xl font-black {{ $tone }} mt-1 font-mono">{{ $value }}</div>
                </div>
            @endforeach
        </div>

        {{-- AI generation --}}
        <div class="p-6 rounded-3xl glass-panel border border-slate-800 space-y-4">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <h2 class="text-sm font-extrabold uppercase tracking-wider text-[#38BDF8]">🤖 Generate with AI</h2>
                @if($aiConfigured)
                    <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 text-[10px] font-bold">Claude connected</span>
                @else
                    <span class="px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/30 text-[10px] font-bold">Claude API key not set</span>
                @endif
            </div>

            <form action="{{ route('admin.posts.generate') }}" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Category</label>
                        <select name="category" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:border-sky-400 focus:outline-none">
                            @foreach(\App\Models\Post::CATEGORIES as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Editor Brief <span class="normal-case text-slate-500 font-normal">(optional)</span></label>
                        <input type="text" name="brief" maxlength="1000" placeholder="e.g. Preview the weekend's Premier League fixtures using our published probabilities" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:border-sky-400 focus:outline-none">
                    </div>
                </div>

                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <p class="text-[11px] text-slate-500 max-w-xl">
                        The model is given real upcoming fixtures and their published probabilities, and is told not to invent
                        facts it was not handed.
                        @if(\App\Models\Setting::get('ai_posts_autopublish', '0') === '1')
                            <span class="text-amber-400 font-semibold">Auto-publish is on — generated articles go live without review.</span>
                        @else
                            Articles arrive as drafts for you to read before publishing.
                        @endif
                    </p>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#38BDF8]/10 text-[#38BDF8] border border-[#38BDF8]/40 hover:bg-[#38BDF8]/20 font-extrabold text-xs transition-all">
                        Queue Article
                    </button>
                </div>
            </form>
        </div>

        {{-- Filters --}}
        <div class="flex items-center gap-2 text-xs font-bold">
            @foreach(['' => 'All', 'published' => 'Published', 'draft' => 'Drafts'] as $value => $label)
                <a href="{{ route('admin.posts.index', array_filter(['status' => $value])) }}"
                   class="px-3 py-1.5 rounded-lg {{ (string) $status === (string) $value ? 'bg-indigo-500 text-white' : 'bg-slate-800/60 text-slate-400 hover:text-white' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- Article list --}}
        <div class="rounded-3xl glass-panel border border-slate-800 divide-y divide-slate-800/80 overflow-hidden">
            @forelse($posts as $post)
                <div class="p-5 flex flex-col sm:flex-row sm:items-center gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            @if($post->isAiWritten())
                                <span class="px-2 py-0.5 rounded-full bg-[#38BDF8]/10 text-[#38BDF8] border border-[#38BDF8]/30 text-[10px] font-bold">AI</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full bg-[#F5A623]/10 text-[#F5A623] border border-[#F5A623]/30 text-[10px] font-bold">HUMAN</span>
                            @endif

                            <span class="text-[10px] uppercase tracking-wider text-slate-500 font-bold">{{ $post->categoryLabel() }}</span>

                            @if($post->isScheduled())
                                <span class="px-2 py-0.5 rounded-full bg-indigo-500/10 text-indigo-300 border border-indigo-500/30 text-[10px] font-bold">SCHEDULED</span>
                            @elseif($post->isPublished())
                                <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 text-[10px] font-bold">LIVE</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full bg-slate-700/40 text-slate-400 border border-slate-600/40 text-[10px] font-bold">DRAFT</span>
                            @endif
                        </div>

                        <a href="{{ route('admin.posts.edit', $post) }}" class="block font-bold text-white hover:text-sky-400 truncate">{{ $post->title }}</a>

                        @if($post->hasOrigin())
                            <div class="text-[11px] text-slate-500 mt-1">
                                via <span class="text-slate-400 font-semibold">{{ $post->origin_name }}</span>
                                @if($post->origin_url)
                                    · <a href="{{ $post->origin_url }}" target="_blank" rel="noopener nofollow" class="text-sky-500 hover:underline">original</a>
                                @endif
                            </div>
                        @endif

                        <div class="text-[11px] text-slate-500 mt-1">
                            {{ $post->author?->name ?? 'Automated' }}
                            · {{ $post->published_at?->format('d M Y H:i') ?? 'not scheduled' }}
                            · {{ $post->views }} views
                            @if($post->ai_model)
                                · <span class="font-mono">{{ $post->ai_model }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('blog.show', $post) }}" target="_blank" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] font-bold">View</a>
                        <a href="{{ route('admin.posts.edit', $post) }}" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] font-bold">Edit</a>

                        <form action="{{ route('admin.posts.toggle', $post) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-lg text-[11px] font-bold {{ $post->status === 'published' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/30 hover:bg-amber-500/20' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/20' }}">
                                {{ $post->status === 'published' ? 'Unpublish' : 'Publish' }}
                            </button>
                        </form>

                        <form action="{{ route('admin.posts.destroy', $post) }}" method="POST" onsubmit="return confirm('Delete this article permanently?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-rose-500/10 text-rose-400 border border-rose-500/30 hover:bg-rose-500/20 text-[11px] font-bold">Delete</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="p-10 text-center text-sm text-slate-500">
                    No articles yet. Write one, or queue an AI draft above.
                </div>
            @endforelse
        </div>

        {{ $posts->links() }}
    </div>
@endsection
