<?php

namespace App\Services;

use Anthropic\Client;
use Anthropic\Core\Exceptions\AnthropicException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Messages\JSONOutputFormat;
use Anthropic\Messages\OutputConfig;
use App\Models\GameMatch;
use App\Models\NewsSource;
use App\Models\Post;
use App\Models\Setting;
use App\Support\FeedItem;
use Illuminate\Support\Facades\Log;

/**
 * Claude-backed article writing for the news desk.
 *
 * Returns a draft Post or null; whether that draft goes live is the caller's
 * decision (see AdminPostController and the ai_posts_autopublish setting).
 * Nothing here publishes on its own — an unreviewed model article on a
 * gambling site is a compliance problem, not a content win.
 */
class PostGenerationService
{
    public const DEFAULT_MODEL = 'claude-opus-5';

    /** Fixtures quoted to the model, so an article is grounded in real data. */
    protected const CONTEXT_FIXTURES = 8;

    public function __construct(protected ?Client $client = null) {}

    public function isConfigured(): bool
    {
        return $this->apiKey() !== '';
    }

    protected function apiKey(): string
    {
        $key = Setting::credential('claude_api_key', 'services.claude.key');

        return str_contains(strtoupper($key), 'MOCK') ? '' : $key;
    }

    protected function model(): string
    {
        return (string) Setting::get('blog_ai_model')
            ?: (string) Setting::get('claude_model', self::DEFAULT_MODEL);
    }

    protected function client(): Client
    {
        return $this->client ??= new Client(apiKey: $this->apiKey());
    }

    /**
     * Write an article and store it as a draft.
     *
     * @param  string  $category  One of Post::CATEGORIES.
     * @param  string|null  $brief  Admin steer; when absent the model picks an
     *                              angle from the upcoming fixture list.
     */
    public function generate(string $category, ?string $brief = null, ?int $authorId = null): ?Post
    {
        if (! $this->isConfigured()) {
            Log::warning('Post generation skipped: Claude is not configured.');

            return null;
        }

        if (! array_key_exists($category, Post::CATEGORIES)) {
            $category = 'news';
        }

        $payload = $this->requestArticle($this->prompt($category, $brief), $category);

        return $this->persist($payload, $category, ['author_id' => $authorId]);
    }

    /**
     * Write an original article in response to an outside report.
     *
     * The feed entry is context, never copy: the model is asked for its own
     * piece, grounded in the platform's fixtures and probabilities, and the
     * published article credits and links the original. Deduped on the feed
     * guid so a story is covered once.
     */
    public function rewrite(FeedItem $item, NewsSource $source): ?Post
    {
        if (! $this->isConfigured()) {
            Log::warning('Rewrite skipped: Claude is not configured.', ['source' => $source->name]);

            return null;
        }

        if (Post::where('origin_hash', $item->hash())->exists()) {
            return null;
        }

        $category = array_key_exists($source->category, Post::CATEGORIES) ? $source->category : 'news';
        $payload = $this->requestArticle($this->rewritePrompt($item, $source), $category);

        $post = $this->persist($payload, $category, [
            'news_source_id' => $source->id,
            'origin_guid' => mb_substr($item->guid, 0, 500),
            'origin_url' => $item->url ? mb_substr($item->url, 0, 500) : null,
            'origin_name' => $source->name,
            'origin_hash' => $item->hash(),
        ]);

        if ($post !== null) {
            $source->increment('items_rewritten');
        }

        return $post;
    }

    /**
     * Validate a model payload and store it as an article.
     *
     * @param  array<string, mixed>|null  $payload
     * @param  array<string, mixed>  $attributes  Origin/authorship columns.
     */
    protected function persist(?array $payload, string $category, array $attributes = []): ?Post
    {
        if ($payload === null) {
            return null;
        }

        $title = trim((string) ($payload['title'] ?? ''));
        $body = trim((string) ($payload['body'] ?? ''));

        // A title-only response is a failed generation, not a stub to publish.
        if ($title === '' || mb_strlen($body) < 200) {
            Log::warning('Claude returned an unusably short article', [
                'category' => $category,
                'title_length' => mb_strlen($title),
                'body_length' => mb_strlen($body),
            ]);

            return null;
        }

        $autoPublish = (string) Setting::get('ai_posts_autopublish', '0') === '1';

        return Post::create($attributes + [
            'title' => $title,
            'slug' => Post::uniqueSlug($title),
            'category' => $category,
            'excerpt' => trim((string) ($payload['excerpt'] ?? '')) ?: null,
            'body' => $body,
            'source' => Post::SOURCE_AI,
            'ai_model' => $this->model(),
            'status' => $autoPublish ? 'published' : 'draft',
            'published_at' => $autoPublish ? now() : null,
            'meta_description' => mb_substr(trim((string) ($payload['meta_description'] ?? '')), 0, 320) ?: null,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function requestArticle(string $prompt, string $category): ?array
    {
        try {
            $message = $this->client()->messages->create(
                maxTokens: 8192,
                messages: [['role' => 'user', 'content' => $prompt]],
                model: $this->model(),
                outputConfig: OutputConfig::with(
                    effort: (string) Setting::get('claude_effort', ClaudePredictionService::DEFAULT_EFFORT),
                    format: JSONOutputFormat::with(schema: $this->schema()),
                ),
                system: 'You are the staff writer for a football analytics publication. '
                    .'Write in plain, specific prose. Never invent scorelines, transfer fees, quotes, '
                    .'injuries, or statistics: if you were not given a fact, write around it rather than '
                    .'filling the gap. Never promise winnings, guaranteed results, or risk-free betting.',
            );
        } catch (APIStatusException $e) {
            Log::error('Claude article request failed', [
                'category' => $category,
                'status' => $e->getCode(),
                'message' => $e->getMessage(),
            ]);

            return null;
        } catch (AnthropicException $e) {
            Log::error('Claude article transport failure', [
                'category' => $category,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        if ($message->stopReason === 'refusal') {
            Log::warning('Claude declined the article request', ['category' => $category]);

            return null;
        }

        return $this->extractPayload($message);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function extractPayload(object $message): ?array
    {
        foreach ($message->content ?? [] as $block) {
            if (($block->type ?? null) !== 'text') {
                continue;
            }

            if (is_array($block->parsed ?? null)) {
                return $block->parsed;
            }

            $decoded = json_decode($block->text ?? '', true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        Log::warning('Claude article response contained no usable payload.');

        return null;
    }

    protected function prompt(string $category, ?string $brief): string
    {
        $lines = [
            'Write one article for a football predictions website.',
            'Category: '.(Post::CATEGORIES[$category] ?? $category),
            '',
        ];

        if (filled($brief)) {
            $lines[] = 'Editor brief (follow it): '.trim((string) $brief);
            $lines[] = '';
        }

        $fixtures = $this->fixtureContext();

        if ($fixtures !== '') {
            $lines[] = 'Upcoming fixtures already on the site, with the model\'s own probabilities.';
            $lines[] = 'These are the only match facts you have. Do not add others.';
            $lines[] = $fixtures;
            $lines[] = '';
        }

        $lines[] = 'Requirements:';
        $lines[] = '- 500 to 800 words, in 4 to 7 paragraphs.';
        $lines[] = '- Plain text paragraphs separated by a blank line. No HTML and no markdown headings.';
        $lines[] = '- Open with the substance, not with a throat-clearing preamble.';
        $lines[] = '- Where you cite a probability, attribute it to the site\'s model.';
        $lines[] = '- Include a responsible-gambling note in the closing paragraph.';
        $lines[] = '- The title must be under 70 characters and must not promise a certain outcome.';

        return implode("\n", $lines);
    }

    /**
     * The brief for an article responding to an outside report.
     *
     * Deliberately not "rewrite this". A close paraphrase of someone else's
     * reporting is both a copyright problem and worthless to readers who can
     * click through to the original. What earns the page is our own angle:
     * what the story means for the fixtures and probabilities we publish.
     */
    protected function rewritePrompt(FeedItem $item, NewsSource $source): string
    {
        $lines = [
            'A football story has been reported elsewhere. Write our own original article responding to it.',
            'Category: '.(Post::CATEGORIES[$source->category] ?? 'Football News'),
            '',
            'The outside report:',
            'Publication: '.$source->name,
            'Headline: '.$item->title,
        ];

        if ($item->summary !== '') {
            $lines[] = 'Summary as published: '.$item->summary;
        }

        $lines[] = '';

        $fixtures = $this->fixtureContext();

        if ($fixtures !== '') {
            $lines[] = 'Our upcoming fixtures and our model\'s published probabilities:';
            $lines[] = $fixtures;
            $lines[] = '';
        }

        $lines[] = 'Rules, in order of importance:';
        $lines[] = '- Do not reproduce or closely paraphrase the wording above. Write a genuinely new piece.';
        $lines[] = '- Do not quote the original article. Do not invent quotes from anyone.';
        $lines[] = '- The only facts you have about this story are in the summary above. Treat them as reported '
            .'claims, not confirmed fact, and attribute them in the text (for example "'.$source->name.' reports that…").';
        $lines[] = '- Lead with the analysis a predictions site can add: what it changes for the fixtures and '
            .'probabilities listed above, or for the teams involved. If it changes nothing measurable, say so plainly.';
        $lines[] = '- If the story has no bearing on football betting or the fixtures we cover, write a short, '
            .'straight news item instead of inflating it.';
        $lines[] = '- 400 to 700 words, plain text paragraphs separated by a blank line, no HTML or markdown headings.';
        $lines[] = '- Write your own headline under 70 characters. Do not reuse the headline above.';
        $lines[] = '- Include a responsible-gambling note in the closing paragraph.';

        return implode("\n", $lines);
    }

    /**
     * Upcoming fixtures and their published probabilities, as grounding.
     *
     * Passing real rows is what keeps the article from being generic filler,
     * and gives the model something true to be specific about.
     */
    protected function fixtureContext(): string
    {
        $matches = GameMatch::with('predictions')
            ->where('kickoff_at', '>=', now())
            ->orderBy('kickoff_at')
            ->take(self::CONTEXT_FIXTURES)
            ->get();

        $lines = [];

        foreach ($matches as $match) {
            $picks = $match->predictions
                ->map(fn ($p) => "{$p->market}: {$p->pick} at ".round($p->probability * 100).'%')
                ->implode(', ');

            $lines[] = sprintf(
                '- %s vs %s (%s, %s)%s',
                $match->home_team,
                $match->away_team,
                $match->league,
                $match->kickoff_at?->toDayDateTimeString() ?? 'kickoff TBC',
                $picks !== '' ? ' — '.$picks : '',
            );
        }

        return implode("\n", $lines);
    }

    /**
     * @return array<string, mixed>
     */
    protected function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string'],
                'excerpt' => ['type' => 'string'],
                'meta_description' => ['type' => 'string'],
                'body' => ['type' => 'string'],
            ],
            'required' => ['title', 'excerpt', 'meta_description', 'body'],
            'additionalProperties' => false,
        ];
    }
}
