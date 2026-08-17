<?php

namespace App\Models;

use App\Support\MarketOutcome;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Result extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id',
        'home_score',
        'away_score',
        'ht_home_score',
        'ht_away_score',
        'home_corners',
        'away_corners',
        'home_yellows',
        'away_yellows',
        'actual_outcome',
        'settled_at',
    ];

    protected $casts = [
        // Scores must be ints: the accuracy calculations compare them with ===
        // to detect draws, which silently fails when the driver hands back
        // numeric strings.
        'home_score' => 'integer',
        'away_score' => 'integer',
        'ht_home_score' => 'integer',
        'ht_away_score' => 'integer',
        'home_corners' => 'integer',
        'away_corners' => 'integer',
        'home_yellows' => 'integer',
        'away_yellows' => 'integer',
        'actual_outcome' => 'array',
        'settled_at' => 'datetime',
    ];

    /**
     * Keep the stored outcome snapshot in step with the scores on the row.
     *
     * Corner and card totals arrive on a later pass than the score, so a
     * snapshot written once at settle time would permanently omit them — and a
     * re-run of the result ingestion, which knows nothing about corners, would
     * overwrite any that had been added. Deriving it on save removes both
     * failure modes and means no caller has to remember to refresh it.
     */
    protected static function booted(): void
    {
        static::saving(function (self $result): void {
            $result->actual_outcome = MarketOutcome::actual(
                (int) $result->home_score,
                (int) $result->away_score,
                $result->gradingContext(),
            );
        });
    }

    /**
     * Settle data beyond the full-time score, for markets that need it.
     *
     * Only keys actually present are returned: a market whose data is missing
     * must read as unsettleable rather than be graded against a zero. Corner
     * and card totals join this once a provider supplies them.
     *
     * @return array<string, mixed>
     */
    public function gradingContext(): array
    {
        $context = [];

        if ($this->ht_home_score !== null && $this->ht_away_score !== null) {
            $context['ht_home'] = $this->ht_home_score;
            $context['ht_away'] = $this->ht_away_score;
        }

        if ($this->home_corners !== null && $this->away_corners !== null) {
            $context['corners'] = $this->home_corners + $this->away_corners;
        }

        if ($this->home_yellows !== null && $this->away_yellows !== null) {
            $context['cards'] = $this->home_yellows + $this->away_yellows;
        }

        return $context;
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }
}
