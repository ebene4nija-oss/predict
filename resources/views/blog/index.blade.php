@extends('layouts.app')

@section('title', 'Football News, Tactical Insights & Editorial Announcements — GUARANTEED CORRECT')
@section('meta_description', 'Breaking football news, betting analysis, tactical previews, and site announcements from the Guaranteed Correct newsroom.')
@section('meta_keywords', 'football news, betting analysis, soccer strategy, tactical insights, football newsroom, sports announcements')
@section('canonical', route('blog.index'))

@section('content')
    <x-ad-banner type="header" />

    <div class="max-w-6xl mx-auto space-y-8 py-4">
        <div>
            <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-sky-500/10 text-sky-400 border border-sky-500/30 text-xs font-bold mb-2">
                <span>NEWSROOM</span>
            </div>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-white">News &amp; Announcements</h1>
            <p class="text-sm text-slate-400 mt-1 max-w-2xl">
                Analysis, fixture context and platform updates. Every article says whether a person or the model wrote it.
            </p>
        </div>

        <!-- Official Telegram Community Banner -->
        <x-telegram-banner />

        {{-- Category filter --}}
        <div class="flex items-center gap-2 flex-wrap text-xs font-bold">
            <a href="{{ route('blog.index') }}" class="px-3 py-1.5 rounded-lg {{ $category === null ? 'bg-sky-500 text-white' : 'bg-slate-800/60 text-slate-400 hover:text-white' }}">All</a>
            @foreach(\App\Models\Post::CATEGORIES as $value => $label)
                <a href="{{ route('blog.index', ['category' => $value]) }}" class="px-3 py-1.5 rounded-lg {{ $category === $value ? 'bg-sky-500 text-white' : 'bg-slate-800/60 text-slate-400 hover:text-white' }}">{{ $label }}</a>
            @endforeach
        </div>

        @if($posts->isEmpty())
            <div class="p-12 rounded-3xl glass-panel border border-slate-800 text-center">
                <p class="text-sm text-slate-400">No articles published yet. Check back shortly.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach($posts as $post)
                    <a href="{{ route('blog.show', $post) }}" class="p-6 rounded-3xl glass-panel border border-slate-800 hover:border-sky-500/50 transition-all group flex flex-col">
                        <div class="flex items-center gap-2 mb-3">
                            @if($post->isAiWritten())
                                <span class="px-2 py-0.5 rounded-full bg-[#38BDF8]/10 text-[#38BDF8] border border-[#38BDF8]/30 text-[10px] font-bold">AI WRITTEN</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full bg-[#F5A623]/10 text-[#F5A623] border border-[#F5A623]/30 text-[10px] font-bold">EDITORIAL</span>
                            @endif
                            <span class="text-[10px] uppercase tracking-wider text-slate-500 font-bold">{{ $post->categoryLabel() }}</span>
                        </div>

                        <h2 class="text-lg font-extrabold text-white group-hover:text-sky-400 transition-colors leading-snug">{{ $post->title }}</h2>

                        <p class="text-xs text-slate-400 mt-2 leading-relaxed flex-grow">{{ $post->summary() }}</p>

                        <div class="text-[11px] text-slate-500 mt-4 pt-4 border-t border-slate-800/80">
                            {{ $post->published_at?->format('d M Y') }} · {{ $post->readingMinutes() }} min read
                        </div>
                    </a>
                @endforeach
            </div>

            <!-- In-Content Ad Placement -->
            <x-ad-banner type="in-content" />

            {{ $posts->links() }}
        @endif
    </div>
@endsection
