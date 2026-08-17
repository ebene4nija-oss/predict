<?php

namespace Tests\Feature;

use App\Jobs\GeneratePostJob;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NewsroomTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
    }

    protected function publishedPost(array $attributes = []): Post
    {
        return Post::create(array_merge([
            'title' => 'Weekend Preview',
            'slug' => 'weekend-preview',
            'category' => 'news',
            'body' => str_repeat('A paragraph about the weekend fixtures. ', 20),
            'source' => Post::SOURCE_HUMAN,
            'status' => 'published',
            'published_at' => now()->subHour(),
        ], $attributes));
    }

    public function test_published_articles_are_publicly_readable(): void
    {
        $post = $this->publishedPost();

        $this->get(route('blog.index'))->assertOk()->assertSee('Weekend Preview');
        $this->get(route('blog.show', $post))->assertOk()->assertSee('Weekend Preview');
    }

    public function test_a_draft_is_not_readable_by_the_public(): void
    {
        $post = $this->publishedPost(['status' => 'draft', 'published_at' => null]);

        $this->get(route('blog.show', $post))->assertNotFound();
        $this->get(route('blog.index'))->assertOk()->assertDontSee('Weekend Preview');
    }

    public function test_a_scheduled_article_stays_hidden_until_its_publish_time(): void
    {
        // Status alone is not enough: an article dated for next week must not
        // appear the moment it is saved.
        $post = $this->publishedPost(['published_at' => now()->addWeek()]);

        $this->get(route('blog.show', $post))->assertNotFound();
        $this->get(route('blog.index'))->assertOk()->assertDontSee('Weekend Preview');
    }

    public function test_an_admin_may_preview_an_unpublished_article(): void
    {
        $post = $this->publishedPost(['status' => 'draft', 'published_at' => null]);

        $this->actingAs($this->admin())
            ->get(route('blog.show', $post))
            ->assertOk()
            ->assertSee('still a draft');
    }

    public function test_ai_authorship_is_disclosed_to_readers(): void
    {
        $post = $this->publishedPost([
            'source' => Post::SOURCE_AI,
            'ai_model' => 'claude-opus-5',
        ]);

        $this->get(route('blog.show', $post))
            ->assertOk()
            ->assertSee('AI WRITTEN')
            ->assertSee('drafted by an AI model');
    }

    public function test_human_articles_are_not_labelled_as_ai(): void
    {
        $post = $this->publishedPost();

        $this->get(route('blog.show', $post))
            ->assertOk()
            ->assertSee('EDITORIAL')
            ->assertDontSee('AI WRITTEN');
    }

    public function test_article_bodies_are_escaped_rather_than_rendered_as_html(): void
    {
        // Bodies come from an editor form and from a model. Neither is trusted
        // to emit markup.
        $post = $this->publishedPost([
            'body' => 'Innocent opening paragraph.'."\n\n".'<script>alert(1)</script>',
        ]);

        $this->get(route('blog.show', $post))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_the_newsroom_is_closed_to_non_admins(): void
    {
        // EnsureAdmin bounces browsers to the home page rather than 403-ing.
        $this->get(route('admin.posts.index'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create(['role' => 'free', 'email_verified_at' => now()]))
            ->get(route('admin.posts.index'))
            ->assertRedirect(route('home'));
    }

    public function test_an_admin_can_write_and_publish_an_article(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.posts.store'), [
                'title' => 'Platform Update: Faster Settlement',
                'category' => 'announcement',
                'body' => 'Results now settle hourly rather than daily.',
                'status' => 'published',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $post = Post::firstWhere('title', 'Platform Update: Faster Settlement');

        $this->assertNotNull($post);
        $this->assertSame(Post::SOURCE_HUMAN, $post->source);
        $this->assertSame('platform-update-faster-settlement', $post->slug);
        $this->assertTrue($post->isPublished());
    }

    public function test_publishing_without_a_date_does_not_produce_an_invisible_article(): void
    {
        // A published row with a null published_at fails the published scope
        // and would silently never appear anywhere.
        $this->actingAs($this->admin())
            ->post(route('admin.posts.store'), [
                'title' => 'No Date Supplied',
                'category' => 'news',
                'body' => 'Body text.',
                'status' => 'published',
            ]);

        $post = Post::firstWhere('title', 'No Date Supplied');

        $this->assertNotNull($post->published_at);
        $this->assertTrue($post->isPublished());
    }

    public function test_duplicate_titles_get_distinct_slugs(): void
    {
        $this->publishedPost(['title' => 'Weekend Update', 'slug' => 'weekend-update']);

        $this->assertSame('weekend-update-2', Post::uniqueSlug('Weekend Update'));
    }

    public function test_republishing_keeps_the_original_publish_date(): void
    {
        $post = $this->publishedPost(['published_at' => now()->subDays(5)]);
        $original = $post->published_at;

        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.posts.toggle', $post));
        $this->actingAs($admin)->post(route('admin.posts.toggle', $post));

        $this->assertTrue($post->fresh()->published_at->equalTo($original));
    }

    public function test_generating_an_article_queues_the_job(): void
    {
        Queue::fake();

        Setting::set('claude_api_key', 'sk-ant-test-key');

        $this->actingAs($this->admin())
            ->post(route('admin.posts.generate'), ['category' => 'analysis', 'brief' => 'Cover the weekend.'])
            ->assertRedirect();

        Queue::assertPushed(GeneratePostJob::class, function (GeneratePostJob $job) {
            return $job->category === 'analysis' && $job->brief === 'Cover the weekend.';
        });
    }

    public function test_generation_is_refused_when_claude_is_not_configured(): void
    {
        Queue::fake();

        $this->actingAs($this->admin())
            ->post(route('admin.posts.generate'), ['category' => 'news'])
            ->assertSessionHas('warning');

        Queue::assertNothingPushed();
    }

    public function test_only_published_articles_reach_the_sitemap(): void
    {
        $this->publishedPost(['title' => 'Live Article', 'slug' => 'live-article']);
        $this->publishedPost(['title' => 'Hidden Draft', 'slug' => 'hidden-draft', 'status' => 'draft', 'published_at' => null]);

        $this->get(route('sitemap.xml'))
            ->assertOk()
            ->assertSee('/news/live-article')
            ->assertDontSee('/news/hidden-draft');
    }
}
