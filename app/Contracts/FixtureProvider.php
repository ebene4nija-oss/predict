<?php

namespace App\Contracts;

use App\Support\FixtureData;
use App\Support\ResultData;

/**
 * A source of real fixtures and results.
 *
 * Swapping data vendors should mean writing one class and changing one config
 * value — nothing above this seam knows which provider is in use.
 */
interface FixtureProvider
{
    /**
     * Fixtures kicking off within the next $days days.
     *
     * @return array<int, FixtureData>
     */
    public function upcomingFixtures(int $days = 7): array;

    /**
     * Final scores for fixtures that finished within the last $days days.
     *
     * @return array<int, ResultData>
     */
    public function recentResults(int $days = 3): array;

    /**
     * Short identifier stored on each fixture, so rows can be traced back to
     * the vendor that supplied them.
     */
    public function name(): string;

    /**
     * Whether this provider has everything it needs to make live calls.
     */
    public function isConfigured(): bool;
}
