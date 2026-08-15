<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prediction extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id',
        'market',
        'source',
        'pick',
        'probability',
        'is_top10',
        'is_ai5',
        'rationale',
        'published_at',
        'odds',
    ];

    protected $casts = [
        'probability' => 'float',
        'is_top10' => 'boolean',
        'is_ai5' => 'boolean',
        'published_at' => 'datetime',
        'odds' => 'float',
    ];

    /**
     * Implied probability of the bookmaker's price, including their margin.
     */
    public function impliedProbability(): ?float
    {
        return $this->odds && $this->odds > 1.0 ? 1.0 / $this->odds : null;
    }

    /**
     * Modelled edge over the price: positive means the price is generous
     * relative to our probability. This — not raw hit rate — is what makes a
     * pick worth backing.
     */
    public function edge(): ?float
    {
        $implied = $this->impliedProbability();

        return $implied === null ? null : $this->probability - $implied;
    }

    /**
     * Profit in units from a 1-unit stake, given whether the pick won.
     */
    public function profitUnits(bool $won): ?float
    {
        if (! $this->odds || $this->odds <= 1.0) {
            return null;
        }

        return $won ? $this->odds - 1.0 : -1.0;
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    /**
     * Restrict to predictions on fixtures that have not kicked off yet.
     *
     * Without this the ranking jobs and public lists happily surface picks for
     * matches that were played weeks ago, because probability alone says
     * nothing about whether a fixture is still live.
     */
    public function scopeForUpcomingMatches(Builder $query, int $graceHours = 2): Builder
    {
        return $query->whereHas('match', function (Builder $match) use ($graceHours) {
            $match->where('kickoff_at', '>=', now()->subHours($graceHours));
        });
    }
}
