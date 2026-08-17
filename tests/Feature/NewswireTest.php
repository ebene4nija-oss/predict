<?php

namespace Tests\Feature;

use App\Jobs\IngestNewsFeedsJob;
use App\Models\NewsSource;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use App\Services\PostGenerationService;
use App\Services\RssFeedReader;
use App\Support\FeedItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NewswireTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
    }

    protected function rss(string $pubDate = 'now'): string
    {
        $date = $pubDate === 'now' ? now()->toRfc2822String() : $pubDate;

        return <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0">
          <channel>
            <title>Example Football</title>
            <item>
              <title>Striker ruled out for six weeks</title>
              <link>https://example.com/news/striker-out</link>
              <guid isPermaLink="false">example-12345</guid>
              <description><![CDATA[<p>The forward will miss the next month of fixtures.</p>]]></description>
              <pubDate>{$date}</pubDate>
            </item>
            <item>
              <title>Manager confirms squad rotation</title>
              <link>https://example.com/news/rotation</link>
              <guid isPermaLink="false">example-12346</guid>
              <description>Changes expected for the midweek game.</description>
              <pubDate>{$date}</pubDate>
            </item>
          </channel>
        </rss>
        XML;
    }

    protected function atom(): string
    {
        $date = now()->toIso8601String();

        return <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <feed xmlns="http://www.w3.org/2005/Atom">
          <title>Atom Football</title>
          <entry>
            <title>Transfer window opens</title>
            <link href="https://atom.example/story-1"/>
            <id>tag:atom.example,2026:story-1</id>
            <summary>Clubs begin their winter business.</summary>
            <updated>{$date}</updated>
          </entry>
        </feed>
        XML;
    }

    // ---------------------------------------------------------------- parsing

    public function test_rss_two_point_zero_entries_are_parsed(): void
    {
        $items = app(RssFeedReader::class)->parse($this->rss());

        $this->assertCount(2, $items);
        $this->assertSame('Striker ruled out for six weeks', $items[0]->title);
        $this->assertSame('example-12345', $items[0]->guid);
        $this->assertSame('https://example.com/news/striker-out', $items[0]->url);
        // CDATA-wrapped HTML is reduced to plain text before it reaches a prompt.
        $this->assertSame('The forward will miss the next month of fixtures.', $items[0]->summary);
    }

    public function test_atom_entries_are_parsed(): void
    {
        $items = app(RssFeedReader::class)->parse($this->atom());

        $this->assertCount(1, $items);
        $this->assertSame('Transfer window opens', $items[0]->title);
        $this->assertSame('tag:atom.example,2026:story-1', $items[0]->guid);
        // Atom puts the URL in an href attribute rather than element text.
        $this->assertSame('https://atom.example/story-1', $items[0]->url);
    }

    public function test_malformed_feeds_yield_nothing_rather_than_throwing(): void
    {
        $reader = app(RssFeedReader::class);

        $this->assertSame([], $reader->parse('not xml at all'));
        $this->assertSame([], $reader->parse(''));
        $this->assertSame([], $reader->parse('<rss><channel></channel></rss>'));
    }

    public function test_an_http_failure_yields_nothing(): void
    {
        Http::fake(['*' => Http::response('', 500)]);

        $this->assertSame([], app(RssFeedReader::class)->read('https://example.com/feed.xml'));
    }

    public function test_freshness_is_measured_against_the_configured_window(): void
    {
        $stale = new FeedItem('a', 'Old story', '', null, now()->subDays(5));
        $fresh = new FeedItem('b', 'New story', '', null, now()->subHour());
        $undated = new FeedItem('c', 'Undated story', '', null, null);

        $this->assertFalse($stale->isFresherThan(48));
        $this->assertTrue($fresh->isFresherThan(48));
        // No date is treated as current rather than silently dropped.
        $this->assertTrue($undated->isFresherThan(48));
    }

    // ------------------------------------------------------------- ingestion

    protected function activeSource(array $attributes = []): NewsSource
    {
        return NewsSource::create(array_merge([
            'name' => 'Example Football',
            'feed_url' => 'https://example.com/feed.xml',
            'category' => 'news',
            'is_active' => true,
            'max_per_run' => 2,
            'max_age_hours' => 48,
        ], $attributes));
    }

    /**
     * Stands in for Claude so ingestion can be tested without a live key.
     */
    protected function fakeWriter(): PostGenerationService
    {
        return new class extends PostGenerationService
        {
            public function isConfigured(): bool
            {
                return true;
            }

            protected function requestArticle(string $prompt, string $category): ?array
            {
                return [
                    'title' => 'Our take: '.md5($prompt),
                    'excerpt' => 'Excerpt.',
                    'meta_description' => 'Meta.',
                    'body' => str_repeat('Original analysis of what this means for the fixtures. ', 12),
                ];
            }
        };
    }

    protected function ingest(): void
    {
        app(IngestNewsFeedsJob::class)->handle(app(RssFeedReader::class), $this->fakeWriter());
    }

    public function test_new_stories_become_articles_that_credit_the_source(): void
    {
        Http::fake(['*' => Http::response($this->rss())]);
        $source = $this->activeSource();

        $this->ingest();

        $this->assertSame(2, Post::count());

        $post = Post::firstWhere('origin_guid', 'example-12345');

        $this->assertNotNull($post);
        $this->assertSame(Post::SOURCE_AI, $post->source);
        $this->assertSame('Example Football', $post->origin_name);
        $this->assertSame('https://example.com/news/striker-out', $post->origin_url);
        $this->assertSame($source->id, $post->news_source_id);
        $this->assertTrue($post->hasOrigin());
    }

    public function test_the_original_headline_and_wording_are_not_republished(): void
    {
        Http::fake(['*' => Http::response($this->rss())]);
        $this->activeSource();

        $this->ingest();

        $post = Post::firstWhere('origin_guid', 'example-12345');

        $this->assertNotSame('Striker ruled out for six weeks', $post->title);
        $this->assertStringNotContainsString('The forward will miss the next month of fixtures.', $post->body);
    }

    public function test_a_story_is_never_written_about_twice(): void
    {
        Http::fake(['*' => Http::response($this->rss())]);
        $this->activeSource();

        $this->ingest();
        $this->ingest();
        $this->ingest();

        $this->assertSame(2, Post::count());
    }

    public function test_stale_stories_are_ignored(): void
    {
        // Published a week ago, well outside the source's 48-hour window.
        Http::fake(['*' => Http::response($this->rss(now()->subWeek()->toRfc2822String()))]);
        $this->activeSource();

        $this->ingest();

        $this->assertSame(0, Post::count());
    }

    public function test_the_per_run_ceiling_is_respected(): void
    {
        Http::fake(['*' => Http::response($this->rss())]);
        $this->activeSource(['max_per_run' => 1]);

        $this->ingest();

        $this->assertSame(1, Post::count());
    }

    public function test_inactive_feeds_are_not_polled(): void
    {
        Http::fake(['*' => Http::response($this->rss())]);
        $this->activeSource(['is_active' => false]);

        $this->ingest();

        $this->assertSame(0, Post::count());
        Http::assertNothingSent();
    }

    public function test_articles_land_as_drafts_unless_auto_publish_is_on(): void
    {
        Http::fake(['*' => Http::response($this->rss())]);
        $this->activeSource();

        $this->ingest();

        $this->assertSame(2, Post::where('status', 'draft')->count());
        $this->assertSame(0, Post::published()->count());
    }

    public function test_auto_publish_sends_articles_straight_live(): void
    {
        Setting::set('ai_posts_autopublish', '1');
        Http::fake(['*' => Http::response($this->rss())]);
        $this->activeSource();

        $this->ingest();

        $this->assertSame(2, Post::published()->count());
    }

    public function test_a_feed_that_cannot_be_read_records_its_error(): void
    {
        Http::fake(['*' => Http::response('', 503)]);
        $source = $this->activeSource();

        $this->ingest();

        $source->refresh();

        $this->assertSame(0, Post::count());
        $this->assertNotNull($source->last_error);
        $this->assertFalse($source->isHealthy());
    }

    public function test_the_source_category_decides_where_articles_are_filed(): void
    {
        Http::fake(['*' => Http::response($this->rss())]);
        $this->activeSource(['category' => 'analysis']);

        $this->ingest();

        $this->assertSame(2, Post::where('category', 'analysis')->count());
    }

    // ------------------------------------------------------------------ admin

    public function test_an_admin_can_add_a_feed(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.sources.store'), [
                'name' => 'BBC Sport',
                'feed_url' => 'https://feeds.bbci.co.uk/sport/football/rss.xml',
                'category' => 'news',
                'max_per_run' => 3,
                'max_age_hours' => 24,
                'is_active' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('news_sources', ['name' => 'BBC Sport', 'is_active' => true]);
    }

    public function test_a_non_http_feed_url_is_rejected(): void
    {
        // The server fetches this URL, so it must not accept file:// or similar.
        $this->actingAs($this->admin())
            ->post(route('admin.sources.store'), [
                'name' => 'Local',
                'feed_url' => 'file:///etc/passwd',
                'category' => 'news',
                'max_per_run' => 1,
                'max_age_hours' => 24,
            ])
            ->assertSessionHasErrors('feed_url');

        $this->assertSame(0, NewsSource::count());
    }

    public function test_test_read_reports_on_a_feed_without_writing_anything(): void
    {
        Http::fake(['*' => Http::response($this->rss())]);
        $source = $this->activeSource();

        $this->actingAs($this->admin())
            ->post(route('admin.sources.preview', $source))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(0, Post::count());
    }

    public function test_polling_from_the_dashboard_queues_the_job(): void
    {
        Queue::fake();
        Setting::set('claude_api_key', 'sk-ant-test-key');
        $source = $this->activeSource();

        $this->actingAs($this->admin())
            ->post(route('admin.sources.poll', $source))
            ->assertRedirect();

        Queue::assertPushed(IngestNewsFeedsJob::class, fn ($job) => $job->onlySourceId === $source->id);
    }

    public function test_the_newswire_is_closed_to_non_admins(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'free', 'email_verified_at' => now()]))
            ->get(route('admin.sources.index'))
            ->assertRedirect(route('home'));
    }

    public function test_readers_are_shown_the_credit_and_a_link_to_the_original(): void
    {
        Http::fake(['*' => Http::response($this->rss())]);
        $this->activeSource();
        $this->ingest();

        $post = Post::firstWhere('origin_guid', 'example-12345');
        $post->update(['status' => 'published', 'published_at' => now()->subMinute()]);

        $this->get(route('blog.show', $post))
            ->assertOk()
            ->assertSee('Written in response to reporting by')
            ->assertSee('Example Football')
            ->assertSee('https://example.com/news/striker-out');
    }
}
