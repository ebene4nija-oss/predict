<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * A finished fixture's score, normalised across providers.
 */
readonly class ResultData
{
    public function __construct(
        public string $externalId,
        public int $homeScore,
        public int $awayScore,
        public ?CarbonInterface $finishedAt = null,
    ) {}
}
