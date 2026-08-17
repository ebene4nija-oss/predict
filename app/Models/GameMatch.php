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
        'preview_generated_at',
        'preview_refreshed_at',
        'preview_source',
    ];

    protected $casts = [
        'kickoff_at' => 'datetime',
        'home_form' => 'array',
        'away_form' => 'array',
        'preview_generated_at' => 'datetime',
        'preview_refreshed_at' => 'datetime',
    ];

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
     */
    public function needsPreview(): bool
    {
        if ($this->hasStarted()) {
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
        return filled($this->preview_text)
            && $this->kickoff_at !== null
            && $this->kickoff_at->lte(now()->addDays(self::previewLeadDays())->endOfDay());
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
