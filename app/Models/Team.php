<?php

namespace App\Models;

use App\Services\Stats\MatchStatsService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'provider',
        'name',
        'short_name',
        'tla',
        'crest_url',
        'custom_crest_url',
        'corners_for',
        'corners_against',
        'cards_for',
        'cards_against',
        'stats_matches',
        'stats_updated_at',
        'stats_alias',
    ];

    protected $casts = [
        'corners_for' => 'float',
        'corners_against' => 'float',
        'cards_for' => 'float',
        'cards_against' => 'float',
        'stats_matches' => 'integer',
        'stats_updated_at' => 'datetime',
    ];

    /**
     * Whether this club has a corner and card rate worth modelling from.
     *
     * A club met only in Europe has no domestic-CSV row, and one promoted three
     * weeks ago has too few matches. Both read as "no rate", which is what keeps
     * an unpriceable fixture out of the corner and card lists rather than
     * putting a league-average guess on it.
     */
    public function hasCountStats(): bool
    {
        return $this->corners_for !== null
            && $this->cards_for !== null
            && (int) $this->stats_matches >= MatchStatsService::MIN_SAMPLE;
    }

    /**
     * The crest to render: an admin's override, else the provider's, else none.
     * Callers fall back to initials rather than a broken image.
     */
    public function crest(): ?string
    {
        return $this->custom_crest_url ?: ($this->crest_url ?: null);
    }

    /**
     * Two or three letters for the no-crest fallback.
     */
    public function initials(): string
    {
        if ($this->tla) {
            return strtoupper($this->tla);
        }

        $words = preg_split('/\s+/', trim($this->name)) ?: [];
        $letters = array_map(static fn (string $word): string => mb_substr($word, 0, 1), array_slice($words, 0, 3));

        return strtoupper(implode('', $letters)) ?: '?';
    }

    /**
     * Find or create the club a fixture refers to.
     *
     * Matched on the provider's id when we have one — the display name drifts
     * ("Wolverhampton Wanderers FC" vs "Wolves") and name-keyed lookups are
     * exactly what produced null form data. Falls back to a name match so
     * manually created fixtures still attach to a club.
     */
    public static function resolve(
        ?string $externalId,
        ?string $provider,
        string $name,
        ?string $shortName = null,
        ?string $tla = null,
        ?string $crestUrl = null,
    ): self {
        $attributes = array_filter([
            'name' => $name,
            'short_name' => $shortName,
            'tla' => $tla,
            'crest_url' => $crestUrl,
        ], static fn ($value) => filled($value));

        if (filled($externalId)) {
            $team = static::where('provider', $provider)->where('external_id', $externalId)->first();

            // Adopt a name-only row rather than creating a second record for
            // the same club. Those rows come from the backfill of fixtures
            // that predate team ids, and are already referenced by matches.
            $team ??= static::whereNull('external_id')->where('name', $name)->first();

            if ($team) {
                $team->fill($attributes + ['provider' => $provider, 'external_id' => $externalId]);
                $team->save();

                return $team;
            }

            return static::create($attributes + ['provider' => $provider, 'external_id' => $externalId]);
        }

        $team = static::whereNull('external_id')->where('name', $name)->first();

        if ($team) {
            // Only fill blanks: a manually corrected crest is not overwritten
            // by a later ingestion that happens to carry the provider's.
            $team->fill(array_diff_key($attributes, array_filter($team->only(array_keys($attributes)))));
            $team->save();

            return $team;
        }

        return static::create($attributes + ['provider' => $provider]);
    }

    public function homeMatches(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'home_team_id');
    }

    public function awayMatches(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'away_team_id');
    }
}
