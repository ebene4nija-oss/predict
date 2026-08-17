<?php

namespace App\Services\Fixtures;

use App\Contracts\FixtureProvider;
use App\Models\Setting;
use App\Support\FixtureData;
use App\Support\ResultData;
use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * football-data.org (v4).
 *
 * Chosen because it has a usable free tier, so the pipeline can run on real
 * fixtures without a commercial contract. Any other vendor is a sibling class
 * implementing FixtureProvider — nothing outside this file assumes v4 shapes.
 */
class FootballDataProvider implements FixtureProvider
{
    protected const BASE_URL = 'https://api.football-data.org/v4';

    /** Competition codes fetched by default. */
    protected const DEFAULT_COMPETITIONS = ['PL', 'PD', 'SA', 'BL1', 'FL1', 'CL'];

    /** Standings are stable within a matchday; cache to stay inside rate limits. */
    protected const FORM_CACHE_SECONDS = 3600;

    public function name(): string
    {
        return 'football-data';
    }

    public function isConfigured(): bool
    {
        return $this->token() !== '';
    }

    protected function token(): string
    {
        return Setting::credential('football_data_token', 'services.football_data.token');
    }

    /**
     * @return array<int, string>
     */
    protected function competitions(): array
    {
        $configured = (string) Setting::get('football_data_competitions', '');

        if ($configured === '') {
            return self::DEFAULT_COMPETITIONS;
        }

        return array_values(array_filter(array_map('trim', explode(',', $configured))));
    }

    protected function request(): PendingRequest
    {
        return Http::withHeaders(['X-Auth-Token' => $this->token()])
            ->timeout(20)
            ->retry(2, 500, throw: false);
    }

    /**
     * @return array<int, FixtureData>
     */
    public function upcomingFixtures(int $days = 7): array
    {
        if (! $this->isConfigured()) {
            Log::warning('football-data token is not configured; no fixtures ingested.');

            return [];
        }

        $response = $this->request()->get(self::BASE_URL.'/matches', [
            'dateFrom' => now()->toDateString(),
            'dateTo' => now()->addDays($days)->toDateString(),
            'competitions' => implode(',', $this->competitions()),
        ]);

        if (! $response->successful()) {
            Log::error('football-data fixture fetch failed', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);

            return [];
        }

        $fixtures = [];

        foreach ($response->json('matches') ?? [] as $match) {
            $externalId = (string) ($match['id'] ?? '');
            $home = $match['homeTeam']['name'] ?? null;
            $away = $match['awayTeam']['name'] ?? null;
            $kickoff = $match['utcDate'] ?? null;

            if ($externalId === '' || ! $home || ! $away || ! $kickoff) {
                continue;
            }

            $competitionCode = $match['competition']['code'] ?? null;

            $fixtures[] = new FixtureData(
                externalId: $externalId,
                homeTeam: $home,
                awayTeam: $away,
                league: $match['competition']['name'] ?? 'Unknown competition',
                kickoffAt: Carbon::parse($kickoff),
                status: $this->mapStatus($match['status'] ?? ''),
                homeForm: $this->teamForm($competitionCode, $home),
                awayForm: $this->teamForm($competitionCode, $away),
                homeTeamMeta: $this->teamMeta($match['homeTeam'] ?? []),
                awayTeamMeta: $this->teamMeta($match['awayTeam'] ?? []),
            );
        }

        return $fixtures;
    }

    /**
     * @return array<int, ResultData>
     */
    public function recentResults(int $days = 3): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $response = $this->request()->get(self::BASE_URL.'/matches', [
            'dateFrom' => now()->subDays($days)->toDateString(),
            'dateTo' => now()->toDateString(),
            'competitions' => implode(',', $this->competitions()),
            'status' => 'FINISHED',
        ]);

        if (! $response->successful()) {
            Log::error('football-data result fetch failed', ['status' => $response->status()]);

            return [];
        }

        $results = [];

        foreach ($response->json('matches') ?? [] as $match) {
            $externalId = (string) ($match['id'] ?? '');
            $home = $match['score']['fullTime']['home'] ?? null;
            $away = $match['score']['fullTime']['away'] ?? null;

            // A finished match with no score is a data error, not a 0-0.
            if ($externalId === '' || ! is_numeric($home) || ! is_numeric($away)) {
                continue;
            }

            // v4 carries the half-time score alongside the full-time one; the
            // first-half markets are graded from it. Absent for some fixtures,
            // so it stays null rather than defaulting.
            $htHome = $match['score']['halfTime']['home'] ?? null;
            $htAway = $match['score']['halfTime']['away'] ?? null;

            $results[] = new ResultData(
                externalId: $externalId,
                homeScore: (int) $home,
                awayScore: (int) $away,
                finishedAt: isset($match['lastUpdated']) ? Carbon::parse($match['lastUpdated']) : now(),
                htHomeScore: is_numeric($htHome) ? (int) $htHome : null,
                htAwayScore: is_numeric($htAway) ? (int) $htAway : null,
            );
        }

        return $results;
    }

    /**
     * The club record attached to a fixture side.
     *
     * v4 returns id, name, shortName, tla and crest on every team object; only
     * the name was being read, which is why the platform had no logos and had
     * to join standings to fixtures by name.
     *
     * @param  array<string, mixed>  $team
     * @return array<string, string|null>
     */
    protected function teamMeta(array $team): array
    {
        if (! isset($team['id'])) {
            return [];
        }

        return [
            'external_id' => (string) $team['id'],
            'short_name' => isset($team['shortName']) ? (string) $team['shortName'] : null,
            'tla' => isset($team['tla']) ? (string) $team['tla'] : null,
            'crest_url' => isset($team['crest']) ? (string) $team['crest'] : null,
        ];
    }

    /**
     * Goals for/against per game, from the competition standings table.
     *
     * @return array{gf: float, ga: float}|null
     */
    protected function teamForm(?string $competitionCode, string $teamName): ?array
    {
        if (! $competitionCode) {
            return null;
        }

        return $this->standings($competitionCode)[$teamName] ?? null;
    }

    /**
     * Team name => per-game goals for/against, for one competition.
     *
     * @return array<string, array{gf: float, ga: float}>
     */
    protected function standings(string $competitionCode): array
    {
        return Cache::remember(
            "football_data.standings.{$competitionCode}",
            self::FORM_CACHE_SECONDS,
            function () use ($competitionCode): array {
                $response = $this->request()->get(self::BASE_URL."/competitions/{$competitionCode}/standings");

                if (! $response->successful()) {
                    Log::warning('football-data standings fetch failed', [
                        'competition' => $competitionCode,
                        'status' => $response->status(),
                    ]);

                    return [];
                }

                $form = [];

                foreach ($response->json('standings') ?? [] as $standing) {
                    // TOTAL is the combined home+away table; HOME/AWAY splits
                    // would double-count teams.
                    if (($standing['type'] ?? '') !== 'TOTAL') {
                        continue;
                    }

                    foreach ($standing['table'] ?? [] as $row) {
                        $name = $row['team']['name'] ?? null;
                        $played = (int) ($row['playedGames'] ?? 0);

                        if (! $name || $played < 1) {
                            continue;
                        }

                        $form[$name] = [
                            'gf' => round(((int) ($row['goalsFor'] ?? 0)) / $played, 3),
                            'ga' => round(((int) ($row['goalsAgainst'] ?? 0)) / $played, 3),
                        ];
                    }
                }

                return $form;
            }
        );
    }

    protected function mapStatus(string $status): string
    {
        return match (strtoupper($status)) {
            'FINISHED', 'AWARDED' => 'finished',
            'IN_PLAY', 'PAUSED' => 'in_play',
            'POSTPONED', 'SUSPENDED', 'CANCELLED' => 'off',
            default => 'scheduled',
        };
    }
}
