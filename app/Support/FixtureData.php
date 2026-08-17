<?php

namespace App\Support;

use App\Models\Team;
use Carbon\CarbonInterface;

/**
 * One upcoming fixture, normalised across providers.
 */
readonly class FixtureData
{
    /**
     * @param  array{gf: float, ga: float}|null  $homeForm  Goals for/against per game.
     * @param  array{gf: float, ga: float}|null  $awayForm
     * @param  array<string, string|null>  $homeTeamMeta  Provider club record: external_id, short_name, tla, crest_url.
     * @param  array<string, string|null>  $awayTeamMeta
     */
    public function __construct(
        public string $externalId,
        public string $homeTeam,
        public string $awayTeam,
        public string $league,
        public CarbonInterface $kickoffAt,
        public string $status = 'scheduled',
        public ?array $homeForm = null,
        public ?array $awayForm = null,
        public ?string $h2hSummary = null,
        public ?string $injuryNotes = null,
        public array $homeTeamMeta = [],
        public array $awayTeamMeta = [],
    ) {}

    /**
     * Attributes for persisting to the matches table.
     *
     * Form is omitted when the provider had none, so a refresh never
     * overwrites good form data with nulls.
     *
     * @return array<string, mixed>
     */
    public function toAttributes(string $provider): array
    {
        return array_filter([
            'provider' => $provider,
            'home_team' => $this->homeTeam,
            'away_team' => $this->awayTeam,
            'league' => $this->league,
            'kickoff_at' => $this->kickoffAt,
            'status' => $this->status,
            'home_form' => $this->homeForm,
            'away_form' => $this->awayForm,
            'h2h_summary' => $this->h2hSummary,
            'injury_notes' => $this->injuryNotes,
        ], static fn ($value) => $value !== null);
    }

    /**
     * Upsert both clubs and return the foreign keys for the fixture row.
     *
     * Separate from toAttributes() because it writes: the caller decides when
     * clubs are persisted, and a provider that supplies no club metadata
     * simply yields no ids rather than creating junk rows.
     *
     * @return array{home_team_id?: int, away_team_id?: int}
     */
    public function syncTeams(string $provider): array
    {
        return array_filter([
            'home_team_id' => $this->resolveTeam($provider, $this->homeTeam, $this->homeTeamMeta),
            'away_team_id' => $this->resolveTeam($provider, $this->awayTeam, $this->awayTeamMeta),
        ], static fn ($value) => $value !== null);
    }

    /**
     * @param  array<string, string|null>  $meta
     */
    protected function resolveTeam(string $provider, string $name, array $meta): ?int
    {
        if ($meta === []) {
            return null;
        }

        return Team::resolve(
            externalId: $meta['external_id'] ?? null,
            provider: $provider,
            name: $name,
            shortName: $meta['short_name'] ?? null,
            tla: $meta['tla'] ?? null,
            crestUrl: $meta['crest_url'] ?? null,
        )->id;
    }
}
