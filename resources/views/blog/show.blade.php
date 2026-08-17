@extends('layouts.app')

@section('title', $post->title . ' — Guaranteed Correct')
@section('meta_description', $post->meta_description ?: $post->summary(160))

@if($post->og_image)
    @section('og_image', $post->og_image)
@endif

@section('schema_json_ld')
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
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
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endsection

@section('content')
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

            {{-- Disclosure. The platform already labels AI predictions against
                 expert picks; the same rule has to hold for editorial copy. --}}
            @if($post->isAiWritten())
                <div class="p-4 rounded-2xl bg-[#38BDF8]/5 border border-[#38BDF8]/20 text-[11px] text-slate-400 leading-relaxed">
                    This article was drafted by an AI model ({{ $post->ai_model ?: 'Claude' }}) from the platform's own fixture
                    data and published probabilities. It is commentary, not advice, and nothing here is a guaranteed outcome.
                </div>
            @endif

            {{-- Credit for the report this article responds to. Kept above the
                 body so a reader sees whose reporting it rests on before they
                 read our take on it. --}}
            @if($post->hasOrigin())
                <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 text-[11px] text-slate-400 leading-relaxed">
                    Written in response to reporting by
                    <span class="font-semibold text-slate-200">{{ $post->origin_name }}</span>.
                    @if($post->origin_url)
                        <a href="{{ $post->origin_url }}" target="_blank" rel="noopener nofollow"
                           class="text-[#38BDF8] hover:underline font-semibold">
                            Read the original{{ $post->originDomain() ? ' at '.$post->originDomain() : '' }} &rarr;
                        </a>
                    @endif
                </div>
            @endif

            {{-- Escaped and split on blank lines: article bodies are plain text
                 by design, so no author or model can inject markup. --}}
            <div class="space-y-5 text-[15px] leading-relaxed text-slate-300">
                @foreach(preg_split('/\n\s*\n/', trim($post->body)) as $paragraph)
                    <p>{{ trim($paragraph) }}</p>
                @endforeach
            </div>
        </article>

        <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 text-[11px] text-slate-500 leading-relaxed">
            18+. Betting involves risk and no prediction is certain. Never stake more than you can afford to lose.
        </div>

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
