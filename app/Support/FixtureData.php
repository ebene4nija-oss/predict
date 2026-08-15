<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * One upcoming fixture, normalised across providers.
 */
readonly class FixtureData
{
    /**
     * @param  array{gf: float, ga: float}|null  $homeForm  Goals for/against per game.
     * @param  array{gf: float, ga: float}|null  $awayForm
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
}
