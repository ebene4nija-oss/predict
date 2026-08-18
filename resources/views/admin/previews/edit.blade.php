@extends('layouts.app')

@section('title', 'Edit Preview: ' . $match->home_team . ' vs ' . $match->away_team . ' — Admin')

@section('content')
<div class="w-full max-w-7xl mx-auto space-y-6">
    <!-- Admin Navigation Bar -->
    @include('admin.partials.nav')

    <!-- Flash Message -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm flex items-center justify-between shadow-lg">
            <div class="flex items-center space-x-2.5">
                <svg class="w-5 h-5 text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-emerald-200 font-bold px-2">&times;</button>
        </div>
    @endif

    <!-- Top Action Bar & Title -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 mb-2">
                <a href="{{ route('admin.previews.index') }}" class="inline-flex items-center space-x-1.5 text-xs font-bold text-slate-400 hover:text-sky-400 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <span>Back to Previews</span>
                </a>
                <span class="text-slate-600">•</span>
                <a href="{{ route('admin.matches.edit', $match) }}" class="text-xs text-slate-400 hover:text-white transition-colors">
                    Match Settings
                </a>
            </div>

            <div class="flex items-center space-x-3 flex-wrap gap-y-2">
                <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
                    {{ $match->home_team }} <span class="text-slate-500 font-light">vs</span> {{ $match->away_team }}
                </h1>
                <span class="px-2.5 py-1 rounded-lg bg-slate-800 border border-slate-700 text-xs font-semibold text-slate-300">
                    {{ $match->league }}
                </span>
                <span class="px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-800 text-xs font-mono text-sky-400">
                    {{ $match->kickoff_at ? $match->kickoff_at->format('M d, Y H:i') . ' UTC' : 'TBD' }}
                </span>
            </div>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ $match->canonicalUrl() }}" target="_blank"
               class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-bold border border-slate-700 transition-all shadow-md">
                <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
                <span>View Public Page</span>
            </a>
        </div>
    </div>

    <!-- Main Workspace Grid: Left Column Editor, Right Column Sidebar -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start w-full">
        <!-- Main Form Area (8 cols) -->
        <div class="w-full lg:col-span-8 space-y-6">
            <!-- SEO Google SERP Live Simulation -->
            <div class="p-6 rounded-3xl glass-panel border border-slate-800 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 rounded-full bg-emerald-400 animate-pulse"></div>
                        <h2 class="text-sm font-bold text-white uppercase tracking-wider">Google Search (SERP) Live Simulator</h2>
                    </div>
                    <span class="text-[11px] text-slate-500 font-mono">Live Crawler Simulation</span>
                </div>

                <!-- Google Snippet Card -->
                <div class="p-5 rounded-2xl bg-[#1e2330]/90 border border-slate-700/60 space-y-1.5 font-sans overflow-hidden">
                    <div class="flex items-center space-x-2 text-[11px] text-slate-400">
                        <span class="w-4 h-4 rounded-full bg-slate-700 flex items-center justify-center text-[9px] text-white shrink-0">⚽</span>
                        <span class="text-slate-300 font-medium truncate">{{ $match->canonicalUrl() }}</span>
                    </div>
                    <div id="serp-title" class="text-lg sm:text-xl font-medium text-[#8ab4f8] hover:underline cursor-pointer leading-snug break-words">
                        {{ $match->seoTitle() }}
                    </div>
                    <div id="serp-desc" class="text-xs sm:text-sm text-slate-300 leading-relaxed break-words">
                        {{ $match->seoDescription() }}
                    </div>
                </div>
            </div>

            <!-- Primary Form: Content & SEO -->
            <form action="{{ route('admin.previews.update', $match) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- SEO Meta Fields Card -->
                <div class="p-6 sm:p-8 rounded-3xl glass-panel border border-slate-800 shadow-xl space-y-5">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <h2 class="text-sm font-bold text-white uppercase tracking-wider">Search Engine Optimization (SEO) Metadata</h2>
                        <span class="text-xs text-sky-400 font-bold">High Intent Search Target</span>
                    </div>

                    <!-- SEO Title -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label for="seo_title" class="block text-xs font-bold text-slate-300 uppercase tracking-wider">
                                SEO Title Tag (Max 70 chars recommended)
                            </label>
                            <span id="seo-title-count" class="text-xs font-mono text-slate-400">0 / 70</span>
                        </div>
                        <input type="text" name="seo_title" id="seo_title"
                               value="{{ old('seo_title', $match->seo_title) }}"
                               placeholder="{{ $match->home_team }} vs {{ $match->away_team }} Prediction, H2H & AI Match Preview — {{ $match->league }}"
                               class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl px-4 py-2.5 text-sm focus:border-sky-400 focus:ring-1 focus:ring-sky-400 focus:outline-none transition-all">
                        @error('seo_title')
                            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- SEO Meta Description -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label for="seo_description" class="block text-xs font-bold text-slate-300 uppercase tracking-wider">
                                SEO Meta Description (120-160 chars recommended)
                            </label>
                            <span id="seo-desc-count" class="text-xs font-mono text-slate-400">0 / 160</span>
                        </div>
                        <textarea name="seo_description" id="seo_description" rows="3"
                                  placeholder="{{ $match->seoDescription() }}"
                                  class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl p-3.5 text-sm focus:border-sky-400 focus:ring-1 focus:ring-sky-400 focus:outline-none transition-all leading-relaxed">{{ old('seo_description', $match->seo_description) }}</textarea>
                        @error('seo_description')
                            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Focus Keywords & Headline -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label for="seo_keywords" class="block text-xs font-bold text-slate-300 uppercase tracking-wider">
                                Focus Keywords (comma-separated)
                            </label>
                            <input type="text" name="seo_keywords" id="seo_keywords"
                                   value="{{ old('seo_keywords', $match->seo_keywords) }}"
                                   placeholder="arsenal vs chelsea prediction, premier league betting tips, xg analysis"
                                   class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl px-4 py-2.5 text-xs focus:border-sky-400 focus:ring-1 focus:ring-sky-400 focus:outline-none transition-all font-mono">
                            @error('seo_keywords')
                                <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1.5">
                            <label for="preview_headline" class="block text-xs font-bold text-slate-300 uppercase tracking-wider">
                                Article Editorial Headline (H1 on page)
                            </label>
                            <input type="text" name="preview_headline" id="preview_headline"
                                   value="{{ old('preview_headline', $match->preview_headline) }}"
                                   placeholder="{{ $match->home_team }} vs {{ $match->away_team }} Prediction & Tactical Match Preview"
                                   class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl px-4 py-2.5 text-xs focus:border-sky-400 focus:ring-1 focus:ring-sky-400 focus:outline-none transition-all">
                            @error('preview_headline')
                                <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Comprehensive Match Preview Narrative Editor -->
                <div class="p-6 sm:p-8 rounded-3xl glass-panel border border-slate-800 shadow-xl space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800 pb-4">
                        <div>
                            <h2 class="text-sm font-bold text-white uppercase tracking-wider">Match Preview Narrative & Tactical Analysis</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Comprehensive, structured Markdown content displayed prominently to users and search engine bots.</p>
                        </div>

                        <!-- Editor Tabs -->
                        <div class="flex items-center space-x-1 p-1 rounded-xl bg-slate-900 border border-slate-800 shrink-0">
                            <button type="button" id="tab-btn-write" onclick="switchEditorTab('write')"
                                    class="px-3.5 py-1.5 rounded-lg text-xs font-bold bg-sky-500 text-slate-950 shadow-sm transition-all">
                                ✍️ Editor
                            </button>
                            <button type="button" id="tab-btn-preview" onclick="switchEditorTab('preview')"
                                    class="px-3.5 py-1.5 rounded-lg text-xs font-bold text-slate-400 hover:text-white transition-all">
                                👁️ Live Preview
                            </button>
                        </div>
                    </div>

                    <!-- Markdown Quick Toolbar -->
                    <div class="flex items-center flex-wrap gap-1.5 p-2 rounded-xl bg-slate-900/90 border border-slate-800 text-xs">
                        <button type="button" onclick="insertMarkdown('## ')" class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold transition-colors" title="Section Heading">H2</button>
                        <button type="button" onclick="insertMarkdown('### ')" class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold transition-colors" title="Sub-heading">H3</button>
                        <button type="button" onclick="insertMarkdown('**', '**')" class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold transition-colors" title="Bold Text">B</button>
                        <button type="button" onclick="insertMarkdown('*', '*')" class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 italic transition-colors" title="Italic Text">I</button>
                        <button type="button" onclick="insertMarkdown('- ')" class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 font-mono transition-colors" title="Bullet List">• List</button>
                        <button type="button" onclick="insertMarkdown('> ')" class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors" title="Quote Callout">Quote</button>
                        <button type="button" onclick="insertTable()" class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors" title="Markdown Table">Table</button>
                        <div class="h-4 w-px bg-slate-700 mx-1"></div>
                        <button type="button" onclick="insertTemplate()" class="px-2.5 py-1 rounded bg-indigo-500/20 hover:bg-indigo-500/30 text-indigo-300 font-bold transition-colors">
                            + Insert Section Template
                        </button>
                    </div>

                    <!-- Write Area -->
                    <div id="editor-write-pane" class="space-y-2">
                        <textarea name="preview_text" id="preview_text" rows="18"
                                  placeholder="Write comprehensive match preview in Markdown..."
                                  class="w-full bg-slate-950 border border-slate-700 text-slate-100 rounded-2xl p-4 text-sm font-sans leading-relaxed focus:border-sky-400 focus:ring-1 focus:ring-sky-400 focus:outline-none transition-all">{{ old('preview_text', $match->preview_text) }}</textarea>
                        @error('preview_text')
                            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                        @enderror

                        <div class="flex flex-wrap items-center justify-between text-xs text-slate-500 pt-1 gap-2">
                            <div class="flex items-center space-x-3">
                                <span id="content-word-count" class="font-mono font-bold text-slate-400">0 words</span>
                                <span>•</span>
                                <span id="content-read-time" class="font-mono">~0 min read</span>
                            </div>
                            <span class="text-[11px] text-slate-400">Supports full GitHub-Flavored Markdown</span>
                        </div>
                    </div>

                    <!-- Live Rendered Preview Tab -->
                    <div id="editor-preview-pane" class="hidden p-6 rounded-2xl bg-slate-950/90 border border-slate-800 text-slate-200 min-h-[350px] overflow-y-auto space-y-4">
                        <div id="rendered-preview-content" class="preview-markdown-content text-sm sm:text-base leading-relaxed">
                            <!-- Populated via Javascript -->
                        </div>
                    </div>
                </div>

                <!-- Settings & Action Buttons -->
                <div class="p-6 rounded-3xl glass-panel border border-slate-800 shadow-xl flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-4">
                        <!-- Status Selector -->
                        <div class="flex items-center space-x-2">
                            <label for="preview_status" class="text-xs font-bold text-slate-300 uppercase tracking-wider">Status:</label>
                            <select name="preview_status" id="preview_status" class="bg-slate-900 border border-slate-700 text-white rounded-xl px-3 py-1.5 text-xs font-bold focus:outline-none focus:border-sky-400">
                                <option value="published" {{ old('preview_status', $match->preview_status) === 'published' ? 'selected' : '' }}>Published (Live)</option>
                                <option value="draft" {{ old('preview_status', $match->preview_status) === 'draft' ? 'selected' : '' }}>Draft (Admin Only)</option>
                            </select>
                        </div>

                        <!-- Custom Lock Toggle -->
                        <label class="inline-flex items-center space-x-2 cursor-pointer bg-slate-900/90 px-3 py-1.5 rounded-xl border border-slate-800 hover:border-slate-700 transition-colors">
                            <input type="checkbox" name="is_preview_custom" value="1" {{ old('is_preview_custom', $match->isCustomPreview()) ? 'checked' : '' }}
                                   class="rounded bg-slate-800 border-slate-700 text-sky-500 focus:ring-sky-400">
                            <span class="text-xs font-bold text-slate-300">🔒 Lock from Auto-Pipeline</span>
                        </label>
                    </div>

                    <div class="flex items-center space-x-3 justify-end">
                        <a href="{{ route('admin.previews.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs transition-all">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-extrabold text-xs shadow-lg shadow-sky-500/25 transition-all">
                            Save Preview & SEO Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Sidebar Area (4 cols) -->
        <div class="w-full lg:col-span-4 space-y-6">
            <!-- AI Generator Assistant Card -->
            <div class="p-6 rounded-3xl glass-panel border border-indigo-500/30 shadow-2xl space-y-4">
                <div class="flex items-center space-x-2 text-indigo-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <h3 class="text-sm font-extrabold uppercase tracking-wider text-white">Gemini AI Preview Generator</h3>
                </div>

                <p class="text-xs text-slate-400 leading-relaxed">
                    Generate an in-depth, multi-section tactical preview enriched with keyword optimization and Poisson model calculations.
                </p>

                <form action="{{ route('admin.previews.generate', $match) }}" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <label for="instruction" class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                            Optional Focus / Tone Instructions
                        </label>
                        <input type="text" name="instruction" id="instruction"
                               placeholder="e.g. Focus on high press and key absences"
                               class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl px-3 py-2 text-xs focus:border-indigo-400 focus:outline-none">
                    </div>

                    <button type="submit" onclick="return confirm('Generate / regenerate match preview using Gemini AI? (Any unsaved manual text will be replaced with fresh AI output)');"
                            class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-indigo-500 to-sky-500 hover:from-indigo-400 hover:to-sky-400 text-white text-xs font-black shadow-lg shadow-indigo-500/25 transition-all flex items-center justify-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span>{{ blank($match->preview_text) ? 'Generate AI Preview' : 'Regenerate with AI' }}</span>
                    </button>
                </form>

                <div class="pt-2 border-t border-slate-800 text-[11px] text-slate-500 space-y-1">
                    <div><strong>Engine:</strong> {{ app(\App\Services\PreviewGenerationService::class)->model() }}</div>
                    <div><strong>Source:</strong> <span class="uppercase font-mono font-semibold text-slate-400">{{ $match->preview_source ?: 'None' }}</span></div>
                    @if($match->preview_generated_at)
                        <div><strong>Last Run:</strong> {{ $match->preview_generated_at->format('M d, H:i') }}</div>
                    @endif
                </div>
            </div>

            <!-- SEO Audit & Score Card -->
            <div class="p-6 rounded-3xl glass-panel border border-slate-800 shadow-xl space-y-3">
                <h3 class="text-sm font-extrabold uppercase tracking-wider text-white">SEO Health & Checklist</h3>
                <ul class="space-y-2 text-xs">
                    <li id="check-title" class="flex items-center space-x-2 text-slate-400">
                        <span class="status-icon text-slate-600">○</span>
                        <span>Title length (40-75 characters)</span>
                    </li>
                    <li id="check-desc" class="flex items-center space-x-2 text-slate-400">
                        <span class="status-icon text-slate-600">○</span>
                        <span>Meta description (100-200 characters)</span>
                    </li>
                    <li id="check-words" class="flex items-center space-x-2 text-slate-400">
                        <span class="status-icon text-slate-600">○</span>
                        <span>Comprehensive word count (250+ words)</span>
                    </li>
                    <li id="check-headings" class="flex items-center space-x-2 text-slate-400">
                        <span class="status-icon text-slate-600">○</span>
                        <span>Structured H2/H3 section headings</span>
                    </li>
                    <li id="check-keywords" class="flex items-center space-x-2 text-slate-400">
                        <span class="status-icon text-slate-600">○</span>
                        <span>Target keywords configured</span>
                    </li>
                </ul>
            </div>

            <!-- Match Context Reference Cheat Sheet -->
            <div class="p-6 rounded-3xl glass-panel border border-slate-800 shadow-xl space-y-4">
                <h3 class="text-sm font-extrabold uppercase tracking-wider text-white">Match Data & Statistics</h3>

                <!-- Poisson Probabilities -->
                @if($match->predictions->count() > 0)
                    <div class="space-y-2">
                        <div class="text-[11px] font-bold text-slate-400 uppercase">Calculated AI Probabilities:</div>
                        <div class="space-y-1.5">
                            @foreach($match->predictions as $pred)
                                <div class="flex items-center justify-between p-2 rounded-xl bg-slate-900 border border-slate-800 text-xs min-w-0">
                                    <span class="font-bold text-slate-300 truncate mr-2">{{ uppercase_mkt($pred->market) }}: {{ $pred->pick }}</span>
                                    <span class="font-mono font-black text-emerald-400 shrink-0">{{ round($pred->probability * 100, 1) }}%</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Team Form Ratings -->
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 min-w-0">
                        <div class="font-bold text-white truncate" title="{{ $match->home_team }}">{{ $match->home_team }}</div>
                        <div class="text-slate-400 text-[11px] mt-1">GF: {{ is_array($match->home_form) ? ($match->home_form['gf'] ?? '1.8') : '1.8' }}</div>
                        <div class="text-slate-400 text-[11px]">GA: {{ is_array($match->home_form) ? ($match->home_form['ga'] ?? '1.0') : '1.0' }}</div>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 min-w-0">
                        <div class="font-bold text-white truncate" title="{{ $match->away_team }}">{{ $match->away_team }}</div>
                        <div class="text-slate-400 text-[11px] mt-1">GF: {{ is_array($match->away_form) ? ($match->away_form['gf'] ?? '1.2') : '1.2' }}</div>
                        <div class="text-slate-400 text-[11px]">GA: {{ is_array($match->away_form) ? ($match->away_form['ga'] ?? '1.5') : '1.5' }}</div>
                    </div>
                </div>

                <!-- H2H & Injury Notes -->
                <div class="space-y-2 text-xs">
                    <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800">
                        <span class="block text-[10px] font-bold text-slate-500 uppercase">H2H History:</span>
                        <span class="text-slate-300 text-[11px] leading-relaxed">{{ $match->h2h_summary ?: 'No head-to-head records stored.' }}</span>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800">
                        <span class="block text-[10px] font-bold text-slate-500 uppercase">Squad Absences:</span>
                        <span class="text-slate-300 text-[11px] leading-relaxed">{{ $match->injury_notes ?: 'No critical absences recorded.' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Client-side Interactive Script for SERP Preview, SEO Audit, and Markdown Parsing -->
<script>
    const defaultTitle = @json($match->seoTitle());
    const defaultDesc = @json($match->seoDescription());

    function updateSerpAndAudit() {
        const titleInput = document.getElementById('seo_title');
        const descInput = document.getElementById('seo_description');
        const textInput = document.getElementById('preview_text');
        const keywordsInput = document.getElementById('seo_keywords');

        const titleVal = (titleInput && titleInput.value.trim()) || defaultTitle;
        const descVal = (descInput && descInput.value.trim()) || defaultDesc;
        const textVal = (textInput && textInput.value) || '';
        const keywordsVal = (keywordsInput && keywordsInput.value.trim()) || '';

        // Update SERP preview
        const serpTitleEl = document.getElementById('serp-title');
        const serpDescEl = document.getElementById('serp-desc');
        if (serpTitleEl) serpTitleEl.textContent = titleVal;
        if (serpDescEl) serpDescEl.textContent = descVal;

        // Update counts
        const titleCountEl = document.getElementById('seo-title-count');
        const descCountEl = document.getElementById('seo-desc-count');
        const wordCountEl = document.getElementById('content-word-count');
        const readTimeEl = document.getElementById('content-read-time');

        const titleLen = titleVal.length;
        const descLen = descVal.length;
        const words = textVal.trim() ? textVal.trim().split(/\s+/).length : 0;
        const readTime = Math.max(1, Math.ceil(words / 200));

        if (titleCountEl) {
            titleCountEl.textContent = titleLen + ' / 70 chars';
            titleCountEl.className = titleLen >= 40 && titleLen <= 75 ? 'text-xs font-mono text-emerald-400 font-bold' : 'text-xs font-mono text-amber-400';
        }

        if (descCountEl) {
            descCountEl.textContent = descLen + ' / 160 chars';
            descCountEl.className = descLen >= 100 && descLen <= 200 ? 'text-xs font-mono text-emerald-400 font-bold' : 'text-xs font-mono text-amber-400';
        }

        if (wordCountEl) wordCountEl.textContent = words + ' words';
        if (readTimeEl) readTimeEl.textContent = '~' + readTime + ' min read';

        // Update Checklist
        updateCheckItem('check-title', titleLen >= 40 && titleLen <= 75);
        updateCheckItem('check-desc', descLen >= 100 && descLen <= 200);
        updateCheckItem('check-words', words >= 250);
        updateCheckItem('check-headings', textVal.includes('## '));
        updateCheckItem('check-keywords', keywordsVal.length > 5);
    }

    function updateCheckItem(id, passed) {
        const el = document.getElementById(id);
        if (!el) return;
        const icon = el.querySelector('.status-icon');
        if (passed) {
            el.className = 'flex items-center space-x-2 text-emerald-400 font-semibold';
            if (icon) { icon.textContent = '✓'; icon.className = 'status-icon text-emerald-400 font-bold'; }
        } else {
            el.className = 'flex items-center space-x-2 text-slate-400';
            if (icon) { icon.textContent = '○'; icon.className = 'status-icon text-slate-600'; }
        }
    }

    function insertMarkdown(prefix, suffix = '') {
        const textarea = document.getElementById('preview_text');
        if (!textarea) return;
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const selected = textarea.value.substring(start, end);
        const replacement = prefix + (selected || 'Text') + suffix;
        textarea.value = textarea.value.substring(0, start) + replacement + textarea.value.substring(end);
        textarea.focus();
        textarea.setSelectionRange(start + prefix.length, start + replacement.length - suffix.length);
        updateSerpAndAudit();
    }

    function insertTable() {
        const tableTemplate = "\n\n| Metric | Home | Away |\n|---|---|---|\n| Expected Goals (xG) | 1.85 | 1.10 |\n| Clean Sheet Probability | 38% | 22% |\n| Poisson Prediction | Over 2.5 | Under 3.5 |\n\n";
        const textarea = document.getElementById('preview_text');
        if (!textarea) return;
        textarea.value += tableTemplate;
        updateSerpAndAudit();
    }

    function insertTemplate() {
        const template = "\n\n## Match Overview & Tactical Context\n"
            + "Detailed tactical overview of this clash...\n\n"
            + "## Tactical Analysis & Key Matchups\n"
            + "Analysis of style of play, pressing triggers, and transition threats...\n\n"
            + "## Head-to-Head & Key Statistical Form Guide\n"
            + "Recent meetings and historical form dynamics...\n\n"
            + "## Team News & Absences\n"
            + "Key player availability and squad updates...\n\n"
            + "## Expected Goals (xG) & Mathematical Probability Outlook\n"
            + "Poisson modeling breakdown and scoring predictions...\n\n"
            + "## Score Prediction & Key Takeaways\n"
            + "- **Key Point**: Tactical summary point.\n"
            + "- **Value Angle**: Expected value pick.\n";

        const textarea = document.getElementById('preview_text');
        if (!textarea) return;
        textarea.value += template;
        updateSerpAndAudit();
    }

    function switchEditorTab(tab) {
        const writePane = document.getElementById('editor-write-pane');
        const previewPane = document.getElementById('editor-preview-pane');
        const btnWrite = document.getElementById('tab-btn-write');
        const btnPreview = document.getElementById('tab-btn-preview');

        if (tab === 'write') {
            writePane.classList.remove('hidden');
            previewPane.classList.add('hidden');
            btnWrite.className = 'px-3.5 py-1.5 rounded-lg text-xs font-bold bg-sky-500 text-slate-950 shadow-sm transition-all';
            btnPreview.className = 'px-3.5 py-1.5 rounded-lg text-xs font-bold text-slate-400 hover:text-white transition-all';
        } else {
            writePane.classList.add('hidden');
            previewPane.classList.remove('hidden');
            btnPreview.className = 'px-3.5 py-1.5 rounded-lg text-xs font-bold bg-sky-500 text-slate-950 shadow-sm transition-all';
            btnWrite.className = 'px-3.5 py-1.5 rounded-lg text-xs font-bold text-slate-400 hover:text-white transition-all';

            // Real-time Markdown rendering
            const text = document.getElementById('preview_text').value;
            const rendered = simpleMarkdownToHtml(text);
            document.getElementById('rendered-preview-content').innerHTML = rendered;
        }
    }

    function simpleMarkdownToHtml(md) {
        if (!md || !md.trim()) return '<p class="text-slate-500 italic">No preview content written yet.</p>';
        
        let clean = md;

        // Convert Markdown tables to HTML tables
        clean = clean.replace(/((?:\|[^\n]+\|\r?\n)+)/g, function(tableBlock) {
            const rows = tableBlock.trim().split(/\r?\n/);
            if (rows.length < 2) return tableBlock;
            let tableHtml = '<table><thead>';
            let isHead = true;
            rows.forEach((row, i) => {
                if (row.match(/^\|[\s\-:|]+\|$/)) {
                    isHead = false;
                    tableHtml += '</thead><tbody>';
                    return;
                }
                const cells = row.split('|').slice(1, -1);
                const tag = isHead ? 'th' : 'td';
                tableHtml += '<tr>' + cells.map(c => `<${tag}>${c.trim()}</${tag}>`).join('') + '</tr>';
            });
            tableHtml += (isHead ? '</thead>' : '</tbody>') + '</table>';
            return tableHtml;
        });

        let html = clean
            .replace(/^#### (.*$)/gim, '<h4>$1</h4>')
            .replace(/^### (.*$)/gim, '<h3>$1</h3>')
            .replace(/^## (.*$)/gim, '<h2>$1</h2>')
            .replace(/\*\*(.*?)\*\*/gim, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/gim, '<em>$1</em>')
            .replace(/`([^`]+)`/gim, '<code>$1</code>')
            .replace(/^\- (.*$)/gim, '<li>$1</li>')
            .replace(/^\d+\. (.*$)/gim, '<li>$1</li>')
            .replace(/^\> (.*$)/gim, '<blockquote>$1</blockquote>')
            .replace(/\n\s*\n/gim, '</p><p>');

        return '<p>' + html + '</p>';
    }

    document.addEventListener('DOMContentLoaded', () => {
        ['seo_title', 'seo_description', 'preview_text', 'seo_keywords'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', updateSerpAndAudit);
            }
        });
        updateSerpAndAudit();
    });
</script>
@endsection
