@extends('layouts.app')

@section('title', $post->title . ' — GUARANTEED CORRECT Newsroom')
@section('meta_description', $post->meta_description ?: $post->summary(160))
@section('canonical', route('blog.show', $post))
@section('og_type', 'article')

@if($post->og_image)
    @section('og_image', $post->og_image)
@endif

@section('schema_json_ld')
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        [
                            '@type' => 'ListItem',
                            'position' => 1,
                            'name' => 'Home',
                            'item' => route('home'),
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 2,
                            'name' => 'News',
                            'item' => route('blog.index'),
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 3,
                            'name' => $post->title,
                            'item' => route('blog.show', $post),
                        ],
                    ],
                ],
                [
                    '@type' => 'NewsArticle',
                    'headline' => $post->title,
                    'description' => $post->meta_description ?: $post->summary(160),
                    'datePublished' => $post->published_at?->toIso8601String(),
                    'dateModified' => $post->updated_at?->toIso8601String(),
                    'author' => [
                        '@type' => $post->isAiWritten() ? 'Organization' : 'Person',
                        'name' => $post->isAiWritten()
                            ? 'Guaranteed Correct AI Newsroom'
                            : ($post->author?->name ?? 'Guaranteed Correct Editorial'),
                    ],
                    'publisher' => [
                        '@type' => 'Organization',
                        'name' => 'Guaranteed Correct',
                    ],
                    'mainEntityOfPage' => [
                        '@type' => 'WebPage',
                        '@id' => route('blog.show', $post),
                    ],
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endsection

@section('content')
    <x-ad-banner type="header" />

    <div class="max-w-3xl mx-auto space-y-8 py-4">
        <a href="{{ route('blog.index') }}" class="text-xs font-bold text-slate-400 hover:text-sky-400">&larr; All news</a>

        @unless($post->isPublished())
            <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-400 text-sm font-semibold">
                Admin preview — this article is {{ $post->isScheduled() ? 'scheduled and not yet visible' : 'still a draft' }}.
            </div>
        @endunless

        <article class="space-y-6">
            <header class="space-y-4">
                <div class="flex items-center gap-2 flex-wrap">
                    @if($post->isAiWritten())
                        <span class="px-2.5 py-1 rounded-full bg-[#38BDF8]/10 text-[#38BDF8] border border-[#38BDF8]/30 text-[10px] font-bold">AI WRITTEN</span>
                    @else
                        <span class="px-2.5 py-1 rounded-full bg-[#F5A623]/10 text-[#F5A623] border border-[#F5A623]/30 text-[10px] font-bold">EDITORIAL</span>
                    @endif
                    <span class="text-[10px] uppercase tracking-wider text-slate-500 font-bold">{{ $post->categoryLabel() }}</span>
                </div>

                <h1 class="text-3xl sm:text-4xl font-extrabold text-white leading-tight">{{ $post->title }}</h1>

                <div class="text-xs text-slate-500">
                    {{ $post->published_at?->format('d F Y') }} · {{ $post->readingMinutes() }} min read
                    @if(! $post->isAiWritten() && $post->author)
                        · by {{ $post->author->name }}
                    @endif
                </div>
            </header>

            {{-- Escaped and split on blank lines: article bodies are plain text
                 by design, so no author or model can inject markup. --}}
            <div class="space-y-5 text-[15px] leading-relaxed text-slate-300">
                @foreach(preg_split('/\n\s*\n/', trim($post->body)) as $paragraph)
                    <p>{{ trim($paragraph) }}</p>
                @endforeach
            </div>
        </article>

        <!-- Official Telegram Community Banner -->
        <x-telegram-banner />

        <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 text-[11px] text-slate-500 leading-relaxed">
            18+. Betting involves risk and no prediction is certain. Never stake more than you can afford to lose.
        </div>

        <!-- In-Content Ad Placement -->
        <x-ad-banner type="in-content" />

        @if($related->isNotEmpty())
            <div class="space-y-4">
                <h2 class="text-sm font-extrabold uppercase tracking-wider text-slate-400">More {{ $post->categoryLabel() }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    @foreach($related as $item)
                        <a href="{{ route('blog.show', $item) }}" class="p-4 rounded-2xl glass-panel border border-slate-800 hover:border-sky-500/50 transition-all">
                            <div class="text-sm font-bold text-white leading-snug">{{ $item->title }}</div>
                            <div class="text-[10px] text-slate-500 mt-2">{{ $item->published_at?->format('d M Y') }}</div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
