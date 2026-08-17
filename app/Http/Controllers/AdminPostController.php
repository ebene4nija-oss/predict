<?php

namespace App\Http\Controllers;

use App\Jobs\GeneratePostJob;
use App\Models\Post;
use App\Services\PostGenerationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminPostController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $posts = Post::with('author')
            ->when(in_array($status, ['draft', 'published'], true), fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'total' => Post::count(),
            'published' => Post::published()->count(),
            'draft' => Post::where('status', 'draft')->count(),
            'ai' => Post::where('source', Post::SOURCE_AI)->count(),
        ];

        $aiConfigured = app(PostGenerationService::class)->isConfigured();

        return view('admin.posts.index', compact('posts', 'counts', 'status', 'aiConfigured'));
    }

    public function create()
    {
        return view('admin.posts.form', ['post' => new Post(['category' => 'news', 'status' => 'draft'])]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePost($request);

        $post = Post::create($this->attributes($validated, $request) + [
            'slug' => Post::uniqueSlug(($validated['slug'] ?? '') ?: $validated['title']),
            'source' => Post::SOURCE_HUMAN,
            'author_id' => $request->user()->id,
        ]);

        return redirect()->route('admin.posts.edit', $post)->with('success', 'Article saved.');
    }

    public function edit(Post $post)
    {
        return view('admin.posts.form', compact('post'));
    }

    public function update(Request $request, Post $post)
    {
        $validated = $this->validatePost($request);

        $post->update($this->attributes($validated, $request) + [
            'slug' => Post::uniqueSlug(($validated['slug'] ?? '') ?: $validated['title'], $post->id),
        ]);

        return redirect()->route('admin.posts.edit', $post)->with('success', 'Article updated.');
    }

    public function destroy(Post $post)
    {
        $post->delete();

        return redirect()->route('admin.posts.index')->with('success', 'Article deleted.');
    }

    /**
     * Flip an article between draft and live without opening the editor.
     */
    public function togglePublish(Post $post)
    {
        if ($post->status === 'published') {
            $post->update(['status' => 'draft']);

            return back()->with('success', "'{$post->title}' moved back to draft.");
        }

        $post->update([
            'status' => 'published',
            // Keep an existing timestamp: re-publishing a corrected article
            // should not shuffle it back to the top of the feed.
            'published_at' => $post->published_at ?? now(),
        ]);

        return back()->with('success', "'{$post->title}' is live.");
    }

    /**
     * Queue an AI-written draft.
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'category' => ['required', Rule::in(array_keys(Post::CATEGORIES))],
            'brief' => 'nullable|string|max:1000',
        ]);

        if (! app(PostGenerationService::class)->isConfigured()) {
            return back()->with('warning', 'Add a Claude API key in Settings before generating articles.');
        }

        GeneratePostJob::dispatch($validated['category'], $validated['brief'] ?? null, $request->user()->id);

        return back()->with('success', 'Article generation queued. Refresh in a moment — it will appear as a draft for review.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatePost(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'category' => ['required', Rule::in(array_keys(Post::CATEGORIES))],
            'excerpt' => 'nullable|string|max:500',
            'body' => 'required|string',
            'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => 'nullable|date',
            'meta_description' => 'nullable|string|max:320',
            'og_image' => 'nullable|url|max:500',
            'is_featured' => 'nullable|boolean',
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function attributes(array $validated, Request $request): array
    {
        $status = $validated['status'];

        return [
            'title' => $validated['title'],
            'category' => $validated['category'],
            'excerpt' => $validated['excerpt'] ?? null,
            'body' => $validated['body'],
            'status' => $status,
            // A post marked published with no date would never satisfy the
            // published scope and would silently vanish from the site.
            'published_at' => $status === 'published'
                ? ($validated['published_at'] ?? now())
                : ($validated['published_at'] ?? null),
            'meta_description' => $validated['meta_description'] ?? null,
            'og_image' => $validated['og_image'] ?? null,
            'is_featured' => $request->boolean('is_featured'),
        ];
    }
}
