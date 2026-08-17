<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A news article or site announcement.
 *
 * Written either by a member of staff or by the model, and the difference is
 * disclosed on the public page — the same rule the platform already applies to
 * AI predictions versus expert picks.
 */
class Post extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'news' => 'Football News',
        'announcement' => 'Site Announcement',
        'analysis' => 'Betting Analysis',
        'guide' => 'Strategy Guide',
    ];

    public const SOURCE_HUMAN = 'human';

    public const SOURCE_AI = 'ai';

    protected $fillable = [
        'title',
        'slug',
        'category',
        'excerpt',
        'body',
        'source',
        'ai_model',
        'status',
        'published_at',
        'author_id',
        'meta_description',
        'og_image',
        'is_featured',
        'news_source_id',
        'origin_guid',
        'origin_url',
        'origin_name',
        'origin_hash',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_featured' => 'boolean',
        'views' => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function newsSource(): BelongsTo
    {
        return $this->belongsTo(NewsSource::class, 'news_source_id');
    }

    /**
     * Whether this article was written in response to an outside report, which
     * has to be credited on the public page.
     */
    public function hasOrigin(): bool
    {
        return filled($this->origin_url) || filled($this->origin_name);
    }

    /**
     * The publication's domain, for a compact credit line.
     */
    public function originDomain(): ?string
    {
        if (! filled($this->origin_url)) {
            return null;
        }

        $host = parse_url($this->origin_url, PHP_URL_HOST);

        return is_string($host) ? preg_replace('/^www\./', '', $host) : null;
    }

    /**
     * Live articles only.
     *
     * A future published_at is a scheduled post and stays hidden until then,
     * so an admin can queue an announcement without a second mechanism.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->status === 'published'
            && $this->published_at !== null
            && $this->published_at->isPast();
    }

    public function isScheduled(): bool
    {
        return $this->status === 'published'
            && $this->published_at !== null
            && $this->published_at->isFuture();
    }

    public function isAiWritten(): bool
    {
        return $this->source === self::SOURCE_AI;
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? Str::headline($this->category);
    }

    /**
     * Roughly how long the article takes to read, in minutes.
     */
    public function readingMinutes(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags($this->body)) / 220));
    }

    /**
     * A short summary for cards and meta tags, derived from the body when the
     * author left the excerpt empty.
     */
    public function summary(int $length = 180): string
    {
        return Str::limit(trim($this->excerpt ?: strip_tags($this->body)), $length);
    }

    /**
     * A URL-safe slug that is unique across posts.
     *
     * Two announcements titled "Weekend Update" are ordinary; a duplicate key
     * violation on save is not, so the collision is resolved here.
     */
    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'post';
        $slug = $base;
        $suffix = 2;

        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
