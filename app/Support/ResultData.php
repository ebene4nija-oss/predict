<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * A finished fixture's score, normalised across providers.
 *
 * Half-time scores are nullable because not every provider reports them and
 * not every fixture has one recorded. Null means "not known", never 0-0.
 */
readonly class ResultData
{
    public function __construct(
        public string $externalId,
        public int $homeScore,
        public int $awayScore,
        public ?CarbonInterface $finishedAt = null,
        public ?int $htHomeScore = null,
        public ?int $htAwayScore = null,
    ) {}

    /**
     * Whether a usable half-time score came through.
     *
     * A half-time score above the full-time one is a provider error — a side
     * cannot un-score — and is rejected rather than stored, because it would
     * settle the first-half markets against an impossible scoreline.
     */
    public function hasHalfTime(): bool
    {
        return $this->htHomeScore !== null
            && $this->htAwayScore !== null
            && $this->htHomeScore >= 0
            && $this->htAwayScore >= 0
            && $this->htHomeScore <= $this->homeScore
            && $this->htAwayScore <= $this->awayScore;
    }
}
