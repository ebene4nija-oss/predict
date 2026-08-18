<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\Setting;
use App\Services\Stats\FootballDataCoUkClient;
use App\Support\CountModel;
use App\Support\MarketRegistry;
use App\Support\PoissonEngine;

/**
 * Expected-goals model. Always available — it needs no third-party service —
 * so it is both the standalone provider and the fallback when Claude is
 * unreachable.
 */
class StatisticalPredictionService
{
    /**
     * Goals per team per game by competition. Used to express a side's form
     * relative to the standard of its league.
     */
    protected const LEAGUE_AVERAGES = [
        'premier league' => 1.42,
        'la liga' => 1.31,
        'serie a' => 1.36,
        'bundesliga' => 1.57,
        'ligue 1' => 1.39,
        'uefa champions league' => 1.48,
        'eredivisie' => 1.62,
    ];

    /**
     * @return array<string, array{pick: string, probability: float, rationale: string}>
     */
    public function generate(GameMatch $match): array
    {
        $leagueAverage = $this->leagueAverage($match->league);
        $homeAdvantage = (float) Setting::get('home_advantage', PoissonEngine::DEFAULT_HOME_ADVANTAGE);

        $lambdas = PoissonEngine::expectedGoals(
            is_array($match->home_form) ? $match->home_form : [],
            is_array($match->away_form) ? $match->away_form : [],
            $leagueAverage,
            $homeAdvantage,
        );

        $probabilities = PoissonEngine::marketProbabilities(
            PoissonEngine::scoreMatrix($lambdas['home'], $lambdas['away'])
        );

        // The first half is modelled from the same expected goals rather than
        // separately, so a fixture cannot be tipped over 2.5 for the match and
        // under 0.5 for the half on inconsistent numbers.
        $firstHalfShare = (float) Setting::get('first_half_share', PoissonEngine::DEFAULT_FIRST_HALF_SHARE);

        $halfTime = PoissonEngine::halfTimeProbabilities(
            PoissonEngine::firstHalfMatrix($lambdas['home'], $lambdas['away'], $firstHalfShare)
        );

        $xg = round($lambdas['home'], 2).' - '.round($lambdas['away'], 2);

        // Each market reports the probability of the side actually tipped, so a
        // 45% "Under 2.5" is published as 45% rather than dressed up.
        $wdl = [
            'Home Win' => $probabilities['home_win'],
            'Draw' => $probabilities['draw'],
            'Away Win' => $probabilities['away_win'],
        ];
        arsort($wdl);
        $wdlPick = (string) array_key_first($wdl);

        // The win market excludes the draw, so it is not a filter on the 1X2
        // pick: a fixture whose likeliest single outcome is a draw can still
        // carry a perfectly rankable 46% away win, and filtering would drop it.
        $winPick = $probabilities['home_win'] >= $probabilities['away_win'] ? 'Home Win' : 'Away Win';
        $winProbability = max($probabilities['home_win'], $probabilities['away_win']);

        $ggYes = $probabilities['gg'];
        $overYes = $probabilities['over_2_5'];

        return [
            'win_draw_loss' => [
                'pick' => $wdlPick,
                'probability' => $wdl[$wdlPick],
                'rationale' => "Expected goals {$xg} ({$match->league} baseline {$leagueAverage}).",
            ],
            'win' => [
                'pick' => $winPick,
                'probability' => $winProbability,
                'rationale' => "Expected goals {$xg}; draw priced out at "
                    .round($probabilities['draw'] * 100, 1).'%.',
            ],
            'gg' => [
                'pick' => $ggYes >= 0.5 ? 'GG (Yes)' : 'NG (No)',
                'probability' => $ggYes >= 0.5 ? $ggYes : 1.0 - $ggYes,
                'rationale' => 'Both teams to score modelled at '.round($ggYes * 100, 1).'%.',
            ],
            'over_2_5' => [
                'pick' => $overYes >= 0.5 ? 'Over 2.5' : 'Under 2.5',
                'probability' => $overYes >= 0.5 ? $overYes : 1.0 - $overYes,
                'rationale' => 'Expected total goals '.round($lambdas['home'] + $lambdas['away'], 2).'.',
            ],
            'fh_over_0_5' => [
                'pick' => $halfTime['over_0_5'] >= 0.5 ? '1H Over 0.5' : '1H Under 0.5',
                'probability' => max($halfTime['over_0_5'], 1.0 - $halfTime['over_0_5']),
                'rationale' => 'First-half expected goals '
                    .round(($lambdas['home'] + $lambdas['away']) * $firstHalfShare, 2)
                    .'; goalless half at '.round((1.0 - $halfTime['over_0_5']) * 100, 1).'%.',
            ],
            'ht_win' => [
                'pick' => $halfTime['home_lead'] >= $halfTime['away_lead'] ? 'HT Home Win' : 'HT Away Win',
                'probability' => max($halfTime['home_lead'], $halfTime['away_lead']),
                'rationale' => 'Level at the break modelled at '
                    .round($halfTime['level'] * 100, 1).'%.',
            ],
            ...$this->countMarkets($match),
        ];
    }

    /**
     * Corner and card markets, modelled from club statistics or competition baselines.
     *
     * @return array<string, array{pick: string, probability: float, rationale: string}>
     */
    protected function countMarkets(GameMatch $match): array
    {
        $home = $match->homeClub;
        $away = $match->awayClub;
        $division = FootballDataCoUkClient::divisionFor($match->league);
        $baseline = FootballDataCoUkClient::baselineFor($division);

        $markets = [];

        // Estimate form multipliers if goals form exists
        $homeGf = is_array($match->home_form) ? ($match->home_form['gf'] ?? null) : null;
        $awayGf = is_array($match->away_form) ? ($match->away_form['gf'] ?? null) : null;
        $homeGa = is_array($match->home_form) ? ($match->home_form['ga'] ?? null) : null;
        $awayGa = is_array($match->away_form) ? ($match->away_form['ga'] ?? null) : null;

        foreach ([
            'corners_over_8_5' => ['corners', 'corners_for', 'corners_against', 'corners', 'corners_var'],
            'cards_over_2_5' => ['yellow cards', 'cards_for', 'cards_against', 'cards', 'cards_var'],
        ] as $key => [$noun, $forField, $againstField, $meanKey, $varianceKey]) {
            $market = MarketRegistry::find($key);

            if ($market?->line === null) {
                continue;
            }

            $homeFor = $home?->{$forField};
            $awayAgainst = $away?->{$againstField};
            $awayFor = $away?->{$forField};
            $homeAgainst = $home?->{$againstField};

            // If specific club counts are null, scale by offensive/defensive form if available
            if ($homeFor === null && $homeGf !== null) {
                $homeFor = ($baseline[$meanKey] / 2.0) * min(1.5, max(0.7, (float) $homeGf / 1.4));
            }
            if ($awayAgainst === null && $awayGa !== null) {
                $awayAgainst = ($baseline[$meanKey] / 2.0) * min(1.5, max(0.7, (float) $awayGa / 1.4));
            }
            if ($awayFor === null && $awayGf !== null) {
                $awayFor = ($baseline[$meanKey] / 2.0) * min(1.5, max(0.7, (float) $awayGf / 1.4));
            }
            if ($homeAgainst === null && $homeGa !== null) {
                $homeAgainst = ($baseline[$meanKey] / 2.0) * min(1.5, max(0.7, (float) $homeGa / 1.4));
            }

            $expected = CountModel::expectedTotal(
                $homeFor,
                $awayAgainst,
                $awayFor,
                $homeAgainst,
                $baseline[$meanKey],
            );

            // The league variance decides the distribution: overdispersed
            // totals get a negative binomial, the rest stay Poisson.
            $over = CountModel::overProbability($expected, $market->line, $baseline[$varianceKey]);

            [$overLabel, $underLabel] = $market->selections();

            $markets[$key] = [
                'pick' => $over >= 0.5 ? $overLabel : $underLabel,
                'probability' => max($over, 1.0 - $over),
                'rationale' => 'Expected '.$noun.' '.round($expected, 1)
                    .' (league baseline '.round($baseline[$meanKey], 1).').',
            ];
        }

        return $markets;
    }

    protected function leagueAverage(?string $league): float
    {
        $key = strtolower(trim((string) $league));

        return self::LEAGUE_AVERAGES[$key]
            ?? (float) Setting::get('default_league_average', PoissonEngine::DEFAULT_LEAGUE_AVERAGE);
    }
}
