<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\Setting;
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

        $ggYes = $probabilities['gg'];
        $overYes = $probabilities['over_2_5'];

        return [
            'win_draw_loss' => [
                'pick' => $wdlPick,
                'probability' => $wdl[$wdlPick],
                'rationale' => "Expected goals {$xg} ({$match->league} baseline {$leagueAverage}).",
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
        ];
    }

    protected function leagueAverage(?string $league): float
    {
        $key = strtolower(trim((string) $league));

        return self::LEAGUE_AVERAGES[$key]
            ?? (float) Setting::get('default_league_average', PoissonEngine::DEFAULT_LEAGUE_AVERAGE);
    }
}
