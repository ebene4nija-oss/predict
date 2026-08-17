<?php

namespace App\Jobs;

use App\Models\NewsSource;
use App\Models\Post;
use App\Services\PostGenerationService;
use App\Services\RssFeedReader;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Polls every active feed and commissions an article for each new story.
 *
 * Bounded on purpose. Each rewrite is a model call that costs money, so a
 * source contributes at most max_per_run articles per poll — attaching a busy
 * wire feed must not turn into a hundred queued generations and a hundred
 * near-identical articles on the front page.
 */
class IngestNewsFeedsJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  int|null  $onlySourceId  Restrict to one feed, for the admin's
     *                                  "poll now" button.
     */
    public function __construct(public ?int $onlySourceId = null) {}

    public function handle(RssFeedReader $reader, PostGenerationService $writer): void
    {
        if (! $writer->isConfigured()) {
            Log::warning('News feed ingestion skipped: Claude is not configured.');

            return;
        }

        $sources = NewsSource::active()
            ->when($this->onlySourceId, fn ($query) => $query->whereKey($this->onlySourceId))
            ->get();

        foreach ($sources as $source) {
            $this->pollSource($source, $reader, $writer);
        }
    }

    protected function pollSource(NewsSource $source, RssFeedReader $reader, PostGenerationService $writer): void
    {
        $items = $reader->read($source->feed_url);

        if ($items === []) {
            $source->recordFetch('No entries could be read from this feed.');

            return;
        }

        $written = 0;

        foreach ($items as $item) {
            if ($written >= max(1, $source->max_per_run)) {
                break;
            }

            // Cheap checks first: skip stale and already-covered stories
            // before spending a model call on them.
            if (! $item->isFresherThan($source->max_age_hours)) {
                continue;
            }

            if (Post::where('origin_hash', $item->hash())->exists()) {
                continue;
            }

            try {
                $post = $writer->rewrite($item, $source);
            } catch (\Throwable $e) {
                // One bad story must not abandon the rest of the feed.
                Log::error('Article rewrite threw', [
                    'source' => $source->name,
                    'headline' => $item->title,
                    'message' => $e->getMessage(),
                ]);

                continue;
            }

            if ($post !== null) {
                $written++;
            }
        }

        $source->recordFetch();

        Log::info('News feed polled', [
            'source' => $source->name,
            'entries' => count($items),
            'written' => $written,
        ]);
    }
}
