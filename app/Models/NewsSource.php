<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A syndication feed the newsroom watches.
 *
 * Items are never republished; they are the prompt for an original article
 * that credits and links back to the original report.
 */
class NewsSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'feed_url',
        'category',
        'is_active',
        'max_per_run',
        'max_age_hours',
        'last_fetched_at',
        'last_error',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_fetched_at' => 'datetime',
        'max_per_run' => 'integer',
        'max_age_hours' => 'integer',
        'items_rewritten' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Record the outcome of a polling run.
     *
     * The error is stored rather than only logged, so a feed that has gone
     * dead is visible in the admin panel instead of failing silently for weeks.
     */
    public function recordFetch(?string $error = null): void
    {
        $this->forceFill([
            'last_fetched_at' => now(),
            'last_error' => $error === null ? null : mb_substr($error, 0, 500),
        ])->save();
    }

    public function isHealthy(): bool
    {
        return $this->last_error === null;
    }
}
