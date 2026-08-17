<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * One finished match from the stats CSV.
 *
 * The source also carries opening and closing bookmaker prices (1X2, over/under
 * 2.5 and Asian handicap). They are not parsed here: nothing consumes odds yet,
 * and the fields would be dead weight until Prediction::odds is populated.
 */
readonly class MatchStatsRow
{
    public function __construct(
        public string $division,
        public CarbonInterface $playedOn,
        public string $homeTeam,
        public string $awayTeam,
        public int $homeGoals,
        public int $awayGoals,
        public ?int $homeCorners = null,
        public ?int $awayCorners = null,
        public ?int $homeYellows = null,
        public ?int $awayYellows = null,
    ) {}

    public function hasCorners(): bool
    {
        return $this->homeCorners !== null && $this->awayCorners !== null;
    }

    public function hasCards(): bool
    {
        return $this->homeYellows !== null && $this->awayYellows !== null;
    }

    public function totalCorners(): ?int
    {
        return $this->hasCorners() ? $this->homeCorners + $this->awayCorners : null;
    }

    public function totalYellows(): ?int
    {
        return $this->hasCards() ? $this->homeYellows + $this->awayYellows : null;
    }
}
