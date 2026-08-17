@extends('layouts.app')

@section('title', ($post->exists ? 'Edit Article' : 'New Article') . ' — Admin Portal')

@section('content')
    <div class="max-w-4xl mx-auto space-y-6 py-4">
        {{-- Admin Navigation Bar --}}
        @include('admin.partials.nav')

        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm font-semibold">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm space-y-1">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div>
                <a href="{{ route('admin.posts.index') }}" class="text-xs font-bold text-slate-400 hover:text-sky-400">&larr; Back to Newsroom</a>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white mt-1">{{ $post->exists ? 'Edit Article' : 'New Article' }}</h1>
            </div>

            @if($post->exists && $post->isAiWritten())
                <div class="px-4 py-2 rounded-xl bg-[#38BDF8]/10 border border-[#38BDF8]/30 text-[11px] text-[#38BDF8]">
                    Drafted by <span class="font-mono font-bold">{{ $post->ai_model }}</span>. Edits keep the AI label —
                    readers are told a model wrote the first draft either way.
                </div>
            @endif
        </div>

        <form action="{{ $post->exists ? route('admin.posts.update', $post) : route('admin.posts.store') }}" method="POST" class="space-y-6">
            @csrf
            @if($post->exists)
                @method('PUT')
            @endif

            <div class="p-6 sm:p-8 rounded-3xl glass-panel border border-slate-800 space-y-5">
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Title</label>
                    <input type="text" name="title" value="{{ old('title', $post->title) }}" required maxlength="255" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:border-sky-400 focus:outline-none">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Category</label>
                        <select name="category" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:border-sky-400 focus:outline-none">
                            @foreach(\App\Models\Post::CATEGORIES as $value => $label)
                                <option value="{{ $value }}" @selected(old('category', $post->category) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-2">URL Slug</label>
                        <input type="text" name="slug" value="{{ old('slug', $post->slug) }}" placeholder="auto-generated from the title" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-sky-400 focus:outline-none">
                        <span class="text-[11px] text-slate-500 mt-1 block">Changing this breaks any existing links to the article.</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Excerpt</label>
                    <textarea name="excerpt" rows="2" maxlength="500" placeholder="Short summary shown on cards. Derived from the body when blank." class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:border-sky-400 focus:outline-none">{{ old('excerpt', $post->excerpt) }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Body</label>
                    <textarea name="body" rows="18" required class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm leading-relaxed focus:border-sky-400 focus:outline-none">{{ old('body', $post->body) }}</textarea>
                    <span class="text-[11px] text-slate-500 mt-1 block">Plain text. Separate paragraphs with a blank line — they are escaped and rendered as paragraphs, so HTML is not interpreted.</span>
                </div>
            </div>

            <div class="p-6 sm:p-8 rounded-3xl glass-panel border border-slate-800 space-y-5">
                <h3 class="text-sm font-extrabold uppercase tracking-wider text-emerald-400">Publishing</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Status</label>
                        <select name="status" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:border-emerald-400 focus:outline-none">
                            <option value="draft" @selected(old('status', $post->status) === 'draft')>Draft</option>
                            <option value="published" @selected(old('status', $post->status) === 'published')>Published</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Publish At</label>
                        <input type="datetime-local" name="published_at" value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-emerald-400 focus:outline-none">
                        <span class="text-[11px] text-slate-500 mt-1 block">A future time with status "Published" schedules the article; it stays hidden until then.</span>
                    </div>
                </div>

                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $post->is_featured)) class="w-4 h-4 rounded bg-slate-900 border-slate-600 text-sky-500 focus:ring-sky-500">
                    <span class="text-xs font-bold text-slate-300 uppercase">Feature at the top of the news page</span>
                </label>
            </div>

            <div class="p-6 sm:p-8 rounded-3xl glass-panel border border-slate-800 space-y-5">
                <h3 class="text-sm font-extrabold uppercase tracking-wider text-amber-400">Search & Social</h3>

                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Meta Description</label>
                    <textarea name="meta_description" rows="2" maxlength="320" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:border-amber-400 focus:outline-none">{{ old('meta_description', $post->meta_description) }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Social Share Image URL</label>
                    <input type="url" name="og_image" value="{{ old('og_image', $post->og_image) }}" placeholder="https://…" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm font-mono focus:border-amber-400 focus:outline-none">
                    <span class="text-[11px] text-slate-500 mt-1 block">Falls back to the site's default share image when blank.</span>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.posts.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs">Cancel</a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-extrabold text-xs shadow-lg shadow-sky-500/25 transition-all">
                    {{ $post->exists ? 'Save Changes' : 'Create Article' }}
                </button>
            </div>
        </form>
    </div>
@endsection
