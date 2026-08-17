<?php

namespace App\Services\Stats;

use App\Models\GameMatch;
use App\Models\Result;
use App\Models\Team;
use App\Support\MatchStatsRow;
use App\Support\TeamNameMatcher;
use Illuminate\Support\Facades\Log;

/**
 * Turns the stats CSVs into the two things the count markets need: rolling
 * per-club rates to model with, and per-match totals to settle against.
 *
 * Rates are built from the CSV directly rather than from our own results table.
 * The CSV holds a club's whole season whether or not we ever ingested those
 * fixtures, so a club's corner rate does not depend on how long this platform
 * has been running.
 */
class MatchStatsService
{
    /**
     * Matches per club behind a rate.
     *
     * Long enough to be a rate rather than a run of form, short enough that a
     * summer of transfers does not dominate. Spans the season boundary on
     * purpose: in August the window is mostly last season, by November it is
     * mostly this one.
     */
    public const SAMPLE_SIZE = 20;

    /** Below this, a club's own rate is noise and the league baseline is used. */
    public const MIN_SAMPLE = 6;

    /** How far a CSV date may sit from our kickoff and still be the same match. */
    protected const DATE_TOLERANCE_DAYS = 1;

    public function __construct(protected FootballDataCoUkClient $client) {}

    /**
     * Recompute corner and card rates for every club in a covered competition.
     *
     * @return int Clubs updated.
     */
    public function refreshTeamRates(): int
    {
        $updated = 0;

        foreach ($this->coveredLeagues() as $league => $division) {
            $rows = $this->client->rows($division);

            if ($rows === []) {
                continue;
            }

            $rates = $this->aggregate($rows);

            if ($rates === []) {
                continue;
            }

            $names = array_combine(array_keys($rates), array_keys($rates));

            foreach ($this->teamsIn($league) as $team) {
                $alias = TeamNameMatcher::bestMatch($team->name, $names);

                if ($alias === null) {
                    // Left alone rather than zeroed: a club we cannot map keeps
                    // whatever rates it already had, and a club that never
                    // mapped simply has none.
                    Log::info('No stats row matched a club', ['team' => $team->name, 'division' => $division]);

                    continue;
                }

                $rate = $rates[$alias];

                $team->forceFill([
                    'corners_for' => round($rate['corners_for'], 3),
                    'corners_against' => round($rate['corners_against'], 3),
                    'cards_for' => round($rate['cards_for'], 3),
                    'cards_against' => round($rate['cards_against'], 3),
                    'stats_matches' => $rate['matches'],
                    'stats_updated_at' => now(),
                    'stats_alias' => (string) $alias,
                ])->save();

                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Fill in corner and card totals for finished fixtures that lack them.
     *
     * @return int Results updated.
     */
    public function settleCounts(int $days = 7): int
    {
        $settled = 0;

        $matches = GameMatch::query()
            ->whereHas('result', fn ($query) => $query->whereNull('home_corners'))
            ->where('kickoff_at', '>=', now()->subDays($days))
            ->where('kickoff_at', '<=', now())
            ->with('result')
            ->get();

        $rowsByDivision = [];

        foreach ($matches as $match) {
            $division = FootballDataCoUkClient::divisionFor($match->league);

            if (! $division) {
                continue;
            }

            $rowsByDivision[$division] ??= $this->client->rows($division);

            $row = $this->findRow($rowsByDivision[$division], $match);

            if (! $row || ! $row->hasCorners()) {
                continue;
            }

            // actual_outcome is re-derived on save (Result::booted), so the
            // stored snapshot picks up the corner and card outcomes here.
            $match->result->forceFill([
                'home_corners' => $row->homeCorners,
                'away_corners' => $row->awayCorners,
                'home_yellows' => $row->homeYellows,
                'away_yellows' => $row->awayYellows,
            ])->save();

            $settled++;
        }

        return $settled;
    }

    /**
     * The CSV row for one of our fixtures, or null.
     *
     * Matched on both clubs and a date within a day: the CSV records the local
     * matchday while kickoff_at is UTC, so a Sunday-evening fixture can land on
     * either side of midnight.
     *
     * @param  array<int, MatchStatsRow>  $rows
     */
    protected function findRow(array $rows, GameMatch $match): ?MatchStatsRow
    {
        $kickoff = $match->kickoff_at;

        if (! $kickoff) {
            return null;
        }

        foreach ($rows as $row) {
            if (abs($row->playedOn->diffInDays($kickoff, false)) > self::DATE_TOLERANCE_DAYS) {
                continue;
            }

            if (TeamNameMatcher::matches($row->homeTeam, $match->home_team)
                && TeamNameMatcher::matches($row->awayTeam, $match->away_team)) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Per-club rates over the most recent matches.
     *
     * @param  array<int, MatchStatsRow>  $rows  Newest first.
     * @return array<string, array{corners_for: float, corners_against: float, cards_for: float, cards_against: float, matches: int}>
     */
    public function aggregate(array $rows): array
    {
        $totals = [];

        foreach ($rows as $row) {
            if (! $row->hasCorners() || ! $row->hasCards()) {
                continue;
            }

            foreach ([
                [$row->homeTeam, $row->homeCorners, $row->awayCorners, $row->homeYellows, $row->awayYellows],
                [$row->awayTeam, $row->awayCorners, $row->homeCorners, $row->awayYellows, $row->homeYellows],
            ] as [$club, $cornersFor, $cornersAgainst, $cardsFor, $cardsAgainst]) {
                $totals[$club] ??= [
                    'corners_for' => 0, 'corners_against' => 0,
                    'cards_for' => 0, 'cards_against' => 0, 'matches' => 0,
                ];

                // Rows arrive newest first, so this keeps the most recent
                // window rather than whichever matches happen to be parsed.
                if ($totals[$club]['matches'] >= self::SAMPLE_SIZE) {
                    continue;
                }

                $totals[$club]['corners_for'] += $cornersFor;
                $totals[$club]['corners_against'] += $cornersAgainst;
                $totals[$club]['cards_for'] += $cardsFor;
                $totals[$club]['cards_against'] += $cardsAgainst;
                $totals[$club]['matches']++;
            }
        }

        $rates = [];

        foreach ($totals as $club => $total) {
            if ($total['matches'] < self::MIN_SAMPLE) {
                continue;
            }

            $rates[$club] = [
                'corners_for' => $total['corners_for'] / $total['matches'],
                'corners_against' => $total['corners_against'] / $total['matches'],
                'cards_for' => $total['cards_for'] / $total['matches'],
                'cards_against' => $total['cards_against'] / $total['matches'],
                'matches' => $total['matches'],
            ];
        }

        return $rates;
    }

    /**
     * Competitions on the site that the CSVs actually cover.
     *
     * @return array<string, string> League name => division code.
     */
    protected function coveredLeagues(): array
    {
        $covered = [];

        foreach (GameMatch::query()->distinct()->pluck('league') as $league) {
            $division = FootballDataCoUkClient::divisionFor($league);

            if ($division) {
                $covered[$league] = $division;
            }
        }

        return $covered;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Team>
     */
    protected function teamsIn(string $league)
    {
        $ids = GameMatch::where('league', $league)
            ->pluck('home_team_id')
            ->merge(GameMatch::where('league', $league)->pluck('away_team_id'))
            ->filter()
            ->unique();

        return Team::whereIn('id', $ids)->get();
    }
}
