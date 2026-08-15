<?php

namespace App\Services\Fixtures;

use App\Contracts\FixtureProvider;
use App\Support\FixtureData;
use App\Support\ResultData;
use Carbon\Carbon;

/**
 * Invented fixtures for local development and tests.
 *
 * This was previously the only "ingestion" the app had, scheduled daily in
 * production — so the live site was serving predictions for matches that were
 * never going to be played. It is now explicitly a demo driver and refuses to
 * produce anything in production.
 */
class SampleFixtureProvider implements FixtureProvider
{
    public function name(): string
    {
        return 'sample';
    }

    public function isConfigured(): bool
    {
        return ! app()->isProduction();
    }

    /**
     * @return array<int, FixtureData>
     */
    public function upcomingFixtures(int $days = 7): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $fixtures = [
            ['Arsenal', 'Chelsea', 'Premier League', 6, ['gf' => 2.2, 'ga' => 0.8], ['gf' => 1.7, 'ga' => 1.2]],
            ['Real Madrid', 'Barcelona', 'La Liga', 12, ['gf' => 2.5, 'ga' => 0.9], ['gf' => 2.4, 'ga' => 1.1]],
            ['Bayern Munich', 'Paris Saint-Germain', 'UEFA Champions League', 18, ['gf' => 2.8, 'ga' => 1.1], ['gf' => 2.1, 'ga' => 1.0]],
            ['Liverpool', 'Manchester City', 'Premier League', 26, ['gf' => 2.3, 'ga' => 0.9], ['gf' => 2.6, 'ga' => 1.0]],
            ['Inter Milan', 'Juventus', 'Serie A', 32, ['gf' => 1.9, 'ga' => 0.6], ['gf' => 1.4, 'ga' => 0.7]],
            ['Borussia Dortmund', 'Bayer Leverkusen', 'Bundesliga', 48, ['gf' => 2.0, 'ga' => 1.4], ['gf' => 2.4, 'ga' => 1.1]],
        ];

        $out = [];

        foreach ($fixtures as [$home, $away, $league, $hours, $homeForm, $awayForm]) {
            $kickoff = Carbon::now()->addHours($hours);

            if ($kickoff->diffInDays(Carbon::now()) > $days) {
                continue;
            }

            $out[] = new FixtureData(
                // Deterministic per fixture *and* per kickoff date, so repeated
                // runs update the same row instead of accumulating duplicates.
                externalId: 'sample-'.md5($home.$away.$kickoff->toDateString()),
                homeTeam: $home,
                awayTeam: $away,
                league: $league,
                kickoffAt: $kickoff,
                homeForm: $homeForm,
                awayForm: $awayForm,
                h2hSummary: 'Sample head-to-head data (development fixture).',
                injuryNotes: 'Sample team news (development fixture).',
            );
        }

        return $out;
    }

    /**
     * The sample provider invents no results — settle demo fixtures by hand.
     *
     * @return array<int, ResultData>
     */
    public function recentResults(int $days = 3): array
    {
        return [];
    }
}
