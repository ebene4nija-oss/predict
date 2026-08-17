<?php

namespace App\Support;

/**
 * Expected-goals match model.
 *
 * Independent Poisson over-predicts 0-0 and 1-0 and under-predicts 1-1, so the
 * low-score cells carry the Dixon-Coles tau correction. Attack and defence
 * strengths are expressed relative to a league baseline rather than used raw,
 * so a 2.0 goals-per-game side in a high-scoring league is not treated the same
 * as a 2.0 side in a defensive one.
 */
class PoissonEngine
{
    /** Goals per team per game, used when a league baseline is unknown. */
    public const DEFAULT_LEAGUE_AVERAGE = 1.35;

    /** Multiplier applied to the home side's expected goals. */
    public const DEFAULT_HOME_ADVANTAGE = 1.15;

    /**
     * Dixon-Coles low-score dependency. Negative rho lifts 1-1 and suppresses
     * 1-0 / 0-1 relative to independent Poisson.
     */
    public const DEFAULT_RHO = -0.13;

    /**
     * Goals per side to enumerate. 10 keeps the truncated tail below ~0.01% for
     * realistic lambdas; the old cap of 6 discarded meaningful probability mass
     * in high-scoring fixtures.
     */
    public const MAX_GOALS = 10;

    /**
     * Share of a match's expected goals that falls in the first half.
     *
     * First halves are reliably the quieter one — sides start cautiously and
     * chase late — and across the major European leagues roughly 45% of goals
     * arrive before the break. Splitting 50/50 would systematically overprice
     * every first-half market.
     */
    public const DEFAULT_FIRST_HALF_SHARE = 0.45;

    /**
     * Expected goals for each side.
     *
     * @param  array{gf?: float, ga?: float}  $homeForm  Goals for/against per game.
     * @param  array{gf?: float, ga?: float}  $awayForm
     * @return array{home: float, away: float}
     */
    public static function expectedGoals(
        array $homeForm,
        array $awayForm,
        float $leagueAverage = self::DEFAULT_LEAGUE_AVERAGE,
        float $homeAdvantage = self::DEFAULT_HOME_ADVANTAGE,
    ): array {
        $leagueAverage = max(0.4, $leagueAverage);

        // Strengths relative to the league: 1.0 is an average side.
        $homeAttack = max(0.05, ($homeForm['gf'] ?? $leagueAverage)) / $leagueAverage;
        $homeDefence = max(0.05, ($homeForm['ga'] ?? $leagueAverage)) / $leagueAverage;
        $awayAttack = max(0.05, ($awayForm['gf'] ?? $leagueAverage)) / $leagueAverage;
        $awayDefence = max(0.05, ($awayForm['ga'] ?? $leagueAverage)) / $leagueAverage;

        return [
            'home' => self::clampLambda($homeAttack * $awayDefence * $leagueAverage * $homeAdvantage),
            'away' => self::clampLambda($awayAttack * $homeDefence * $leagueAverage),
        ];
    }

    /**
     * Weight a run of recent per-game figures, most recent heaviest.
     *
     * @param  array<int, float>  $values  Oldest first.
     */
    public static function weightedAverage(array $values, float $decay = 0.85): ?float
    {
        $values = array_values(array_filter($values, 'is_numeric'));

        if ($values === []) {
            return null;
        }

        $weighted = 0.0;
        $weightSum = 0.0;
        $count = count($values);

        foreach ($values as $index => $value) {
            // Oldest entry gets the smallest weight.
            $weight = $decay ** ($count - 1 - $index);
            $weighted += $weight * (float) $value;
            $weightSum += $weight;
        }

        return $weightSum > 0 ? $weighted / $weightSum : null;
    }

    /**
     * Joint score probability matrix, indexed [homeGoals][awayGoals].
     *
     * @return array<int, array<int, float>>
     */
    public static function scoreMatrix(
        float $lambdaHome,
        float $lambdaAway,
        float $rho = self::DEFAULT_RHO,
        int $maxGoals = self::MAX_GOALS,
    ): array {
        $homeProbs = self::poissonSeries($lambdaHome, $maxGoals);
        $awayProbs = self::poissonSeries($lambdaAway, $maxGoals);

        $matrix = [];
        $total = 0.0;

        for ($h = 0; $h <= $maxGoals; $h++) {
            for ($a = 0; $a <= $maxGoals; $a++) {
                $probability = $homeProbs[$h] * $awayProbs[$a]
                    * self::tau($h, $a, $lambdaHome, $lambdaAway, $rho);

                // Tau can push a cell marginally negative at extreme rho.
                $probability = max(0.0, $probability);

                $matrix[$h][$a] = $probability;
                $total += $probability;
            }
        }

        // Renormalise: the tau correction and the truncated tail both cost mass.
        if ($total > 0) {
            foreach ($matrix as $h => $row) {
                foreach ($row as $a => $probability) {
                    $matrix[$h][$a] = $probability / $total;
                }
            }
        }

        return $matrix;
    }

    /**
     * Market probabilities derived from a score matrix.
     *
     * Every market returns the probability of the *named* outcome, never of
     * "whichever side we happen to be tipping" — callers pick the side.
     *
     * @param  array<int, array<int, float>>  $matrix
     * @return array{home_win: float, draw: float, away_win: float, gg: float, over_2_5: float}
     */
    public static function marketProbabilities(array $matrix): array
    {
        $homeWin = 0.0;
        $draw = 0.0;
        $awayWin = 0.0;
        $bothScored = 0.0;
        $over = 0.0;

        foreach ($matrix as $h => $row) {
            foreach ($row as $a => $probability) {
                if ($h > $a) {
                    $homeWin += $probability;
                } elseif ($h === $a) {
                    $draw += $probability;
                } else {
                    $awayWin += $probability;
                }

                if ($h > 0 && $a > 0) {
                    $bothScored += $probability;
                }

                if (($h + $a) > 2.5) {
                    $over += $probability;
                }
            }
        }

        return [
            'home_win' => $homeWin,
            'draw' => $draw,
            'away_win' => $awayWin,
            'gg' => $bothScored,
            'over_2_5' => $over,
        ];
    }

    /**
     * Joint score probability matrix for the first half alone.
     *
     * Rho is forced to zero. The Dixon-Coles correction is fitted to full-time
     * scorelines, and reusing that rho against first-half lambdas roughly a
     * third their size would apply a correction calibrated for a distribution
     * this is not — inflating the 1-1 cell of a half where 1-1 is rare.
     *
     * @return array<int, array<int, float>>
     */
    public static function firstHalfMatrix(
        float $lambdaHome,
        float $lambdaAway,
        float $share = self::DEFAULT_FIRST_HALF_SHARE,
        int $maxGoals = self::MAX_GOALS,
    ): array {
        $share = min(0.9, max(0.1, $share));

        return self::scoreMatrix($lambdaHome * $share, $lambdaAway * $share, 0.0, $maxGoals);
    }

    /**
     * Half-time market probabilities derived from a first-half score matrix.
     *
     * @param  array<int, array<int, float>>  $matrix
     * @return array{home_lead: float, level: float, away_lead: float, over_0_5: float}
     */
    public static function halfTimeProbabilities(array $matrix): array
    {
        $homeLead = 0.0;
        $level = 0.0;
        $awayLead = 0.0;

        foreach ($matrix as $h => $row) {
            foreach ($row as $a => $probability) {
                if ($h > $a) {
                    $homeLead += $probability;
                } elseif ($h === $a) {
                    $level += $probability;
                } else {
                    $awayLead += $probability;
                }
            }
        }

        return [
            'home_lead' => $homeLead,
            'level' => $level,
            'away_lead' => $awayLead,
            // Any goal at all is the complement of the goalless cell.
            'over_0_5' => max(0.0, 1.0 - ($matrix[0][0] ?? 0.0)),
        ];
    }

    /**
     * Dixon-Coles low-score dependency factor.
     */
    protected static function tau(int $h, int $a, float $lambdaHome, float $lambdaAway, float $rho): float
    {
        if ($h === 0 && $a === 0) {
            return 1.0 - ($lambdaHome * $lambdaAway * $rho);
        }

        if ($h === 0 && $a === 1) {
            return 1.0 + ($lambdaHome * $rho);
        }

        if ($h === 1 && $a === 0) {
            return 1.0 + ($lambdaAway * $rho);
        }

        if ($h === 1 && $a === 1) {
            return 1.0 - $rho;
        }

        return 1.0;
    }

    /**
     * Poisson probabilities for 0..$maxGoals, computed iteratively so no
     * factorial overflows and the recurrence stays numerically stable.
     *
     * @return array<int, float>
     */
    protected static function poissonSeries(float $lambda, int $maxGoals): array
    {
        $probabilities = [];
        $current = exp(-$lambda);

        for ($k = 0; $k <= $maxGoals; $k++) {
            $probabilities[$k] = $current;
            $current *= $lambda / ($k + 1);
        }

        return $probabilities;
    }

    protected static function clampLambda(float $lambda): float
    {
        return min(6.0, max(0.15, $lambda));
    }
}
