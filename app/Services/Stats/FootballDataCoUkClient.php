<?php

namespace App\Services\Stats;

use App\Support\MatchStatsRow;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Season statistics from football-data.co.uk.
 *
 * Free, unauthenticated season CSVs carrying corners (HC/AC) and yellow cards
 * (HY/AY) alongside the score — the two things the fixture provider does not
 * report at all. Chosen over a paid statistics API because it supplies both
 * halves of the problem: the history to model team rates from, and the
 * per-match totals to settle the published picks against.
 *
 * Domestic competitions only. There is no Champions League file, which is why
 * clubs met only in Europe end up with no rates and no corner or card pick.
 */
class FootballDataCoUkClient
{
    protected const BASE_URL = 'https://www.football-data.co.uk/mmz4281';

    /**
     * Competition name as the fixture provider reports it => CSV division code.
     */
    public const DIVISIONS = [
        'premier league' => 'E0',
        'championship' => 'E1',
        'la liga' => 'SP1',
        'primera division' => 'SP1',
        'serie a' => 'I1',
        'bundesliga' => 'D1',
        'ligue 1' => 'F1',
        'eredivisie' => 'N1',
        'primeira liga' => 'P1',
    ];

    /**
     * League-average match totals, from the completed 2025-26 seasons.
     *
     * Variance is carried alongside the mean because it decides the
     * distribution: corner totals are overdispersed everywhere, card totals are
     * close to Poisson and in Serie A slightly under it.
     *
     * @var array<string, array{corners: float, corners_var: float, cards: float, cards_var: float}>
     */
    public const BASELINES = [
        'E0' => ['corners' => 10.00, 'corners_var' => 10.67, 'cards' => 3.75, 'cards_var' => 3.80],
        'SP1' => ['corners' => 9.67, 'corners_var' => 11.82, 'cards' => 4.41, 'cards_var' => 5.18],
        'I1' => ['corners' => 8.82, 'corners_var' => 9.92, 'cards' => 3.70, 'cards_var' => 3.28],
        'D1' => ['corners' => 9.75, 'corners_var' => 12.52, 'cards' => 3.81, 'cards_var' => 4.37],
        'F1' => ['corners' => 9.59, 'corners_var' => 12.86, 'cards' => 3.74, 'cards_var' => 3.50],
    ];

    /** Used when a division has no measured baseline of its own. */
    public const DEFAULT_BASELINE = [
        'corners' => 9.60, 'corners_var' => 11.50, 'cards' => 3.90, 'cards_var' => 4.00,
    ];

    /** Results do not change once published; the file only grows. */
    protected const CACHE_SECONDS = 21600;

    /**
     * The CSV division code for a competition, or null when it has no file.
     */
    public static function divisionFor(?string $league): ?string
    {
        return self::DIVISIONS[mb_strtolower(trim((string) $league))] ?? null;
    }

    /**
     * @return array{corners: float, corners_var: float, cards: float, cards_var: float}
     */
    public static function baselineFor(?string $division): array
    {
        return self::BASELINES[$division] ?? self::DEFAULT_BASELINE;
    }

    /**
     * Season codes to read, most recent first.
     *
     * Two of them, always. A season that is three weeks old has too few matches
     * per club to derive a rate from, and pretending otherwise would publish
     * corner picks built on two games.
     *
     * @return array<int, string>
     */
    public static function seasonCodes(?Carbon $on = null): array
    {
        $date = $on ?? Carbon::now();

        // Seasons run August to May, so January belongs to the season that
        // started the previous calendar year.
        $startYear = $date->month >= 7 ? $date->year : $date->year - 1;

        $code = static fn (int $year): string => sprintf('%02d%02d', $year % 100, ($year + 1) % 100);

        return [$code($startYear), $code($startYear - 1)];
    }

    /**
     * Finished matches for a division, newest first, across the recent seasons.
     *
     * @return array<int, MatchStatsRow>
     */
    public function rows(string $division, ?Carbon $on = null): array
    {
        $rows = [];

        foreach (self::seasonCodes($on) as $season) {
            foreach ($this->season($division, $season) as $row) {
                $rows[] = $row;
            }
        }

        usort($rows, static fn (MatchStatsRow $a, MatchStatsRow $b) => $b->playedOn <=> $a->playedOn);

        return $rows;
    }

    /**
     * One season file, parsed.
     *
     * @return array<int, MatchStatsRow>
     */
    public function season(string $division, string $season): array
    {
        return Cache::remember(
            "football_data_couk.{$division}.{$season}",
            self::CACHE_SECONDS,
            function () use ($division, $season): array {
                $url = self::BASE_URL."/{$season}/{$division}.csv";

                $response = Http::timeout(30)->retry(2, 500, throw: false)->get($url);

                if (! $response->successful()) {
                    // A season that has not started yet 404s or redirects. That
                    // is expected in August, not an error worth alerting on.
                    Log::info('Stats CSV unavailable', [
                        'division' => $division,
                        'season' => $season,
                        'status' => $response->status(),
                    ]);

                    return [];
                }

                return $this->parse($division, $response->body());
            }
        );
    }

    /**
     * @return array<int, MatchStatsRow>
     */
    public function parse(string $division, string $csv): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($csv)) ?: [];

        if (count($lines) < 2) {
            return [];
        }

        // The file is served with a UTF-8 BOM, which would otherwise become
        // part of the first column name and hide the Div field.
        $header = str_getcsv(preg_replace('/^\x{FEFF}/u', '', array_shift($lines)) ?? '');
        $index = array_flip(array_map('trim', $header));

        $rows = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $cells = str_getcsv($line);

            $value = static function (string $column) use ($cells, $index): ?string {
                $position = $index[$column] ?? null;
                $cell = $position === null ? null : ($cells[$position] ?? null);

                return ($cell === null || trim($cell) === '') ? null : trim($cell);
            };

            $date = $value('Date');
            $home = $value('HomeTeam');
            $away = $value('AwayTeam');
            $homeGoals = $value('FTHG');
            $awayGoals = $value('FTAG');

            if (! $date || ! $home || ! $away || ! is_numeric($homeGoals) || ! is_numeric($awayGoals)) {
                continue;
            }

            $playedOn = $this->parseDate($date);

            if (! $playedOn) {
                continue;
            }

            $number = static fn (?string $raw): ?int => is_numeric($raw) ? (int) $raw : null;

            $rows[] = new MatchStatsRow(
                division: $division,
                playedOn: $playedOn,
                homeTeam: $home,
                awayTeam: $away,
                homeGoals: (int) $homeGoals,
                awayGoals: (int) $awayGoals,
                homeCorners: $number($value('HC')),
                awayCorners: $number($value('AC')),
                homeYellows: $number($value('HY')),
                awayYellows: $number($value('AY')),
            );
        }

        return $rows;
    }

    /**
     * The file uses d/m/y, and switched to a four-digit year mid-history.
     */
    protected function parseDate(string $raw): ?Carbon
    {
        foreach (['d/m/Y', 'd/m/y'] as $format) {
            $parsed = Carbon::createFromFormat($format, $raw);

            if ($parsed !== false) {
                return $parsed->startOfDay();
            }
        }

        return null;
    }
}
