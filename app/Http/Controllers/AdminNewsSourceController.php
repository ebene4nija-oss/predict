<?php

namespace App\Http\Controllers;

use App\Jobs\IngestNewsFeedsJob;
use App\Models\NewsSource;
use App\Models\Post;
use App\Services\PostGenerationService;
use App\Services\RssFeedReader;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminNewsSourceController extends Controller
{
    public function index()
    {
        $sources = NewsSource::withCount('posts')->orderBy('name')->get();
        $aiConfigured = app(PostGenerationService::class)->isConfigured();

        return view('admin.posts.sources', compact('sources', 'aiConfigured'));
    }

    public function store(Request $request)
    {
        NewsSource::create($this->validated($request));

        return back()->with('success', 'Feed added. It will be polled on the next run.');
    }

    public function update(Request $request, NewsSource $source)
    {
        $source->update($this->validated($request));

        // The stored error describes the previous URL/settings; keep it only
        // while the configuration it refers to is unchanged.
        $source->forceFill(['last_error' => null])->save();

        return back()->with('success', "Feed '{$source->name}' updated.");
    }

    public function destroy(NewsSource $source)
    {
        // Articles already written keep their attribution text; only the link
        // back to the feed record goes away.
        $source->delete();

        return back()->with('success', 'Feed removed. Articles already published are untouched.');
    }

    /**
     * Read a feed without writing anything, so an admin can confirm the URL
     * parses before letting it commission articles.
     */
    public function preview(NewsSource $source, RssFeedReader $reader)
    {
        $items = $reader->read($source->feed_url);

        if ($items === []) {
            $source->recordFetch('No entries could be read from this feed.');

            return back()->with('warning', "Nothing could be read from '{$source->name}'. Check the URL is a valid RSS or Atom feed.");
        }

        $source->recordFetch();

        $fresh = collect($items)
            ->filter(fn ($item) => $item->isFresherThan($source->max_age_hours))
            ->reject(fn ($item) => Post::where('origin_hash', $item->hash())->exists())
            ->count();

        $headlines = collect($items)->take(3)->map(fn ($item) => $item->title)->implode(' · ');

        return back()->with('success', sprintf(
            '%d entries read from %s, %d new and in date. Latest: %s',
            count($items),
            $source->name,
            $fresh,
            $headlines,
        ));
    }

    /**
     * Poll now rather than waiting for the scheduler.
     */
    public function poll(Request $request, ?NewsSource $source = null)
    {
        if (! app(PostGenerationService::class)->isConfigured()) {
            return back()->with('warning', 'Add a Claude API key in Settings before polling feeds.');
        }

        IngestNewsFeedsJob::dispatch($source?->id);

        return back()->with('success', 'Feed poll queued. New stories will appear in the newsroom shortly.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            // A server-side fetch of an admin-supplied URL: restricted to
            // http(s) so it cannot be pointed at file:// or a local socket.
            'feed_url' => ['required', 'url:http,https', 'max:500'],
            'category' => ['required', Rule::in(array_keys(Post::CATEGORIES))],
            'max_per_run' => 'required|integer|min:1|max:10',
            'max_age_hours' => 'required|integer|min:1|max:336',
            'is_active' => 'nullable|boolean',
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
