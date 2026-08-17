<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->query('category');

        if (! array_key_exists((string) $category, Post::CATEGORIES)) {
            $category = null;
        }

        $posts = Post::published()
            ->with('author')
            ->when($category, fn ($query) => $query->where('category', $category))
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->paginate(9)
            ->withQueryString();

        return view('blog.index', compact('posts', 'category'));
    }

    public function show(Post $post)
    {
        // Route model binding resolves by slug and does not know about draft
        // or scheduled state; without this an unpublished article is readable
        // by anyone who guesses the URL.
        abort_unless($post->isPublished() || $this->userIsAdmin(), 404);

        $post->load('author');
        $post->increment('views');

        $related = Post::published()
            ->where('category', $post->category)
            ->whereKeyNot($post->id)
            ->orderByDesc('published_at')
            ->take(3)
            ->get();

        return view('blog.show', compact('post', 'related'));
    }

    /**
     * Admins may open an unpublished article to preview it before it goes live.
     */
    protected function userIsAdmin(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }
}
