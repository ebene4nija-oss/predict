<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GameMatch extends Model
{
    use HasFactory;

    protected $table = 'matches';

    /** Fallback lead time when the admin has not set one. */
    public const DEFAULT_PREVIEW_LEAD_DAYS = 7;

    /**
     * Ceiling on the configurable lead time. Fixture feeds get unreliable
     * further out — kickoff times move and fixtures are added late — so a
     * preview written three weeks ahead is likely to be wrong by matchday.
     */
    public const MAX_PREVIEW_LEAD_DAYS = 14;

    /**
     * How close to kickoff a preview gets its one rewrite.
     *
     * A preview written a week out is guessing: form moves, kickoff times
     * shift, and team news does not exist yet. Rewriting once inside this
     * window replaces it with copy based on data that is actually settled,
     * without paying for a rewrite every day in between.
     */
    public const DEFAULT_PREVIEW_REFRESH_DAYS = 2;

    /** Written by the model. */
    public const PREVIEW_SOURCE_MODEL = 'gemini';

    /** Boilerplate, written because the model was unreachable. */
    public const PREVIEW_SOURCE_FALLBACK = 'fallback';

    /** Manually curated or edited by an admin. */
    public const PREVIEW_SOURCE_CUSTOM = 'custom';

    public const PREVIEW_STATUS_PUBLISHED = 'published';
    public const PREVIEW_STATUS_DRAFT = 'draft';

    protected $fillable = [
        'external_id',
        'provider',
        'home_team',
        'away_team',
        'home_team_id',
        'away_team_id',
        'league',
        'kickoff_at',
        'status',
        'home_form',
        'away_form',
        'h2h_summary',
        'injury_notes',
        'preview_text',
        'preview_headline',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'preview_generated_at',
        'preview_refreshed_at',
        'preview_source',
        'preview_status',
        'is_preview_custom',
    ];

    protected $casts = [
        'kickoff_at' => 'datetime',
        'home_form' => 'array',
        'away_form' => 'array',
        'preview_generated_at' => 'datetime',
        'preview_refreshed_at' => 'datetime',
        'is_preview_custom' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (GameMatch $match) {
            if (blank($match->home_team_id) && filled($match->home_team)) {
                $team = Team::where('name', $match->home_team)->first()
                    ?? Team::create(['name' => $match->home_team, 'provider' => $match->provider ?: 'manual']);
                $match->home_team_id = $team->id;
            }

            if (blank($match->away_team_id) && filled($match->away_team)) {
                $team = Team::where('name', $match->away_team)->first()
                    ?? Team::create(['name' => $match->away_team, 'provider' => $match->provider ?: 'manual']);
                $match->away_team_id = $team->id;
            }
        });
    }

    /**
     * How many days ahead the platform ingests fixtures and publishes previews.
     *
     * Clamped rather than trusted: the settings table is editable outside the
     * validated admin form (tinker, a SQL fix-up), and a stray value here would
     * mean either no fixtures at all or a very expensive ingestion run.
     */
    public static function previewLeadDays(): int
    {
        $configured = (int) Setting::get('preview_lead_days', self::DEFAULT_PREVIEW_LEAD_DAYS);

        return max(1, min(self::MAX_PREVIEW_LEAD_DAYS, $configured ?: self::DEFAULT_PREVIEW_LEAD_DAYS));
    }

    /**
     * How many days of fixtures the home page lists.
     *
     * Separate from the content lead time on purpose. Fixtures, predictions and
     * previews are produced across the full lead window so the pages exist and
     * can be indexed; this governs only how much of that the front page puts in
     * front of a visitor. Capped at the lead time — the home page cannot list
     * fixtures that have not been ingested.
     */
    public static function homeFixtureDays(): int
    {
        $lead = self::previewLeadDays();
        $configured = (int) Setting::get('home_fixture_days', $lead);

        return max(1, min($lead, $configured ?: $lead));
    }

    /**
     * How close to kickoff the single preview rewrite happens.
     */
    public static function previewRefreshDays(): int
    {
        $configured = (int) Setting::get('preview_refresh_days', self::DEFAULT_PREVIEW_REFRESH_DAYS);

        return max(1, min(self::previewLeadDays(), $configured ?: self::DEFAULT_PREVIEW_REFRESH_DAYS));
    }

    public function hasStarted(): bool
    {
        return $this->kickoff_at !== null && $this->kickoff_at->isPast();
    }

    /**
     * Whether this fixture's preview should be written on this pass.
     *
     * Three reasons to write, and nothing else: it has never been written; the
     * last attempt fell back to boilerplate because the model was unreachable;
     * or it is now close enough to kickoff for its one refresh on settled data.
     *
     * Manually authored or locked previews (is_preview_custom = true) are never
     * overwritten by the automated background pipeline.
     */
    public function needsPreview(): bool
    {
        if ($this->hasStarted() || $this->is_preview_custom) {
            return false;
        }

        if (blank($this->preview_text) || $this->preview_generated_at === null) {
            return true;
        }

        // Retry placeholders. Without this, a single outage during the nightly
        // run would leave boilerplate on the page until kickoff.
        if ($this->preview_source === self::PREVIEW_SOURCE_FALLBACK) {
            return true;
        }

        return $this->preview_refreshed_at === null && $this->isInPreviewRefreshWindow();
    }

    public function isInPreviewRefreshWindow(): bool
    {
        return $this->kickoff_at !== null
            && $this->kickoff_at->lte(now()->addDays(self::previewRefreshDays())->endOfDay());
    }

    /**
     * Fixtures inside the publishing window: kicking off between now (less a
     * grace period for in-play matches) and the configured lead time.
     *
     * Public listings use this so raising or lowering the lead time changes
     * what visitors see, not just what the ingestion job fetches.
     */
    public function scopeWithinPreviewWindow(Builder $query, int $graceHours = 4): Builder
    {
        return $query->where('kickoff_at', '>=', now()->subHours($graceHours))
            ->where('kickoff_at', '<=', now()->addDays(self::previewLeadDays())->endOfDay());
    }

    /**
     * Fixtures the home page lists.
     *
     * A narrower view of the same window, so the front page can be tightened to
     * the next day or two without withdrawing the rest of the week's fixture
     * pages from the site — those keep their predictions, previews and their
     * place in the sitemap.
     */
    public function scopeForHomeListing(Builder $query, int $graceHours = 4): Builder
    {
        return $query->where('kickoff_at', '>=', now()->subHours($graceHours))
            ->where('kickoff_at', '<=', now()->addDays(self::homeFixtureDays())->endOfDay());
    }

    /**
     * Whether the narrative preview may be shown to visitors yet.
     *
     * Ingestion is already bounded by the lead time, but fixtures added by
     * hand in the admin panel are not, and the setting is meant to govern what
     * gets published — not merely what gets fetched.
     */
    public function previewIsPublishable(): bool
    {
        if ($this->preview_status === self::PREVIEW_STATUS_DRAFT) {
            return false;
        }

        return filled($this->preview_text)
            && $this->kickoff_at !== null
            && $this->kickoff_at->lte(now()->addDays(self::previewLeadDays())->endOfDay());
    }

    /**
     * Effective SEO Title for the match preview.
     */
    public function seoTitle(): string
    {
        if (filled($this->seo_title)) {
            return $this->seo_title;
        }

        return "{$this->home_team} vs {$this->away_team} Prediction, H2H & AI Match Preview — {$this->league}";
    }

    /**
     * Effective SEO Meta Description.
     */
    public function seoDescription(): string
    {
        if (filled($this->seo_description)) {
            return $this->seo_description;
        }

        $date = $this->kickoff_at ? $this->kickoff_at->format('M d, Y H:i') : 'upcoming';

        return "Comprehensive {$this->home_team} vs {$this->away_team} prediction, Poisson expected-goals (xG) statistics, tactical preview, and betting odds breakdown for {$this->league} kickoff {$date}.";
    }

    /**
     * SEO-friendly URL Slug.
     */
    public function slug(): string
    {
        return \Illuminate\Support\Str::slug("{$this->home_team}-vs-{$this->away_team}-prediction-{$this->league}");
    }

    /**
     * Canonical Absolute URL.
     */
    public function canonicalUrl(): string
    {
        return route('matches.show', ['match' => $this->id, 'slug' => $this->slug()]);
    }

    /**
     * Effective SEO Focus Keywords.
     */
    public function seoKeywords(): string
    {
        if (filled($this->seo_keywords)) {
            return $this->seo_keywords;
        }

        return strtolower("{$this->home_team} vs {$this->away_team} prediction, {$this->home_team} vs {$this->away_team} betting tips, {$this->league} AI preview, expected goals xG breakdown, football match analysis");
    }

    /**
     * Editorial Preview Headline.
     */
    public function previewHeadline(): string
    {
        if (filled($this->preview_headline)) {
            return $this->preview_headline;
        }

        return "{$this->home_team} vs {$this->away_team} Prediction & Tactical Match Preview";
    }

    /**
     * Preview word count.
     */
    public function previewWordCount(): int
    {
        if (blank($this->preview_text)) {
            return 0;
        }

        return str_word_count(strip_tags($this->preview_text));
    }

    /**
     * Estimated reading minutes.
     */
    public function previewReadingMinutes(): int
    {
        $words = $this->previewWordCount();

        return max(1, (int) ceil($words / 200));
    }

    /**
     * Clean plain text excerpt of preview, stripped of markdown headings.
     */
    public function previewExcerpt(int $limit = 180): string
    {
        if (blank($this->preview_text)) {
            return "Detailed tactical match preview, head-to-head records, and AI model probability analysis for {$this->home_team} vs {$this->away_team}.";
        }

        $plain = preg_replace('/^#+\s+.*$/m', '', $this->preview_text);
        $plain = preg_replace('/[*_`>#-]/', '', (string) $plain);
        $plain = trim((string) preg_replace('/\s+/', ' ', (string) $plain));

        return \Illuminate\Support\Str::limit($plain ?: "Detailed tactical analysis for {$this->home_team} vs {$this->away_team}.", $limit);
    }

    /**
     * Whether preview is customized by human admin.
     */
    public function isCustomPreview(): bool
    {
        return (bool) $this->is_preview_custom;
    }

    /**
     * Whether preview is in draft state.
     */
    public function isDraftPreview(): bool
    {
        return $this->preview_status === self::PREVIEW_STATUS_DRAFT;
    }

    /**
     * Filter by search term across teams or league.
     */
    public function scopeSearchMatches(Builder $query, ?string $search): Builder
    {
        if (blank($search)) {
            return $query;
        }

        $search = trim($search);

        return $query->where(function (Builder $q) use ($search) {
            $q->where('home_team', 'like', "%{$search}%")
                ->orWhere('away_team', 'like', "%{$search}%")
                ->orWhere('league', 'like', "%{$search}%");
        });
    }

    /**
     * Filter by preview state / source.
     */
    public function scopeWithPreviewFilter(Builder $query, ?string $filter): Builder
    {
        return match ($filter) {
            'missing' => $query->where(function ($q) {
                $q->whereNull('preview_text')->orWhere('preview_text', '');
            }),
            'has_preview' => $query->whereNotNull('preview_text')->where('preview_text', '!=', ''),
            'gemini' => $query->where('preview_source', self::PREVIEW_SOURCE_MODEL),
            'fallback' => $query->where('preview_source', self::PREVIEW_SOURCE_FALLBACK),
            'custom' => $query->where('is_preview_custom', true),
            'draft' => $query->where('preview_status', self::PREVIEW_STATUS_DRAFT),
            'published' => $query->where('preview_status', self::PREVIEW_STATUS_PUBLISHED),
            default => $query,
        };
    }

    public function homeClub(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayClub(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    /**
     * Crest URL for a side, or null when the club is unknown or has no logo.
     */
    public function crestFor(string $side): ?string
    {
        $club = $side === 'home' ? $this->homeClub : $this->awayClub;

        return $club?->crest();
    }

    /**
     * Whether both clubs carry corner and card rates.
     *
     * Gates the count markets. A Champions League tie between two clubs from
     * different domestic files still qualifies if both have rates; a tie
     * involving a club from an uncovered league does not, and gets no corner or
     * card pick rather than one built on a league average.
     */
    public function hasCountStats(): bool
    {
        return (bool) $this->homeClub?->hasCountStats()
            && (bool) $this->awayClub?->hasCountStats();
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class, 'match_id');
    }

    public function expertPicks(): HasMany
    {
        return $this->hasMany(ExpertPick::class, 'match_id');
    }

    public function result(): HasOne
    {
        return $this->hasOne(Result::class, 'match_id');
    }
}
