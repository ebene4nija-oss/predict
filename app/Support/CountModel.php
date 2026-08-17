<?php

namespace App\Support;

/**
 * Over/under probabilities for a match total that is not goals.
 *
 * Corners and cards need no joint score matrix: the only question these markets
 * ask is whether the *total* clears a line, so a one-dimensional count
 * distribution is the whole model.
 *
 * Which distribution matters. Measured over the 2025-26 domestic seasons,
 * corner totals are overdispersed — Ligue 1 averages 9.59 with a variance of
 * 12.86 — while yellow-card totals sit close to Poisson and Serie A is actually
 * *under*dispersed (mean 3.70, variance 3.28). A negative binomial cannot
 * represent underdispersion at all, so the distribution is chosen from the data
 * rather than assumed: negative binomial when the variance exceeds the mean,
 * Poisson otherwise.
 */
class CountModel
{
    /** Totals to enumerate. Corner totals above 30 are vanishingly rare. */
    public const MAX_COUNT = 40;

    /**
     * Probability the match total finishes strictly above the line.
     *
     * @param  float  $mean  Expected total for this fixture.
     * @param  float|null  $variance  League variance of the same total; null uses Poisson.
     */
    public static function overProbability(float $mean, float $line, ?float $variance = null): float
    {
        $mean = max(0.05, $mean);

        $probabilities = self::distribution($mean, $variance);

        $under = 0.0;

        foreach ($probabilities as $count => $probability) {
            if ($count <= $line) {
                $under += $probability;
            }
        }

        return min(1.0, max(0.0, 1.0 - $under));
    }

    /**
     * The count distribution over 0..MAX_COUNT, normalised.
     *
     * @return array<int, float>
     */
    public static function distribution(float $mean, ?float $variance = null): array
    {
        $mean = max(0.05, $mean);

        $probabilities = ($variance !== null && $variance > $mean)
            ? self::negativeBinomial($mean, $variance)
            : self::poisson($mean);

        $total = array_sum($probabilities);

        if ($total <= 0.0) {
            return $probabilities;
        }

        // The truncated tail costs a little mass at high means.
        foreach ($probabilities as $k => $probability) {
            $probabilities[$k] = $probability / $total;
        }

        return $probabilities;
    }

    /**
     * Expected total for a fixture, from both clubs' rates and a league baseline.
     *
     * Strengths are expressed relative to the league rather than used raw, so a
     * side winning 6 corners a game in a 10-corner league is not treated as the
     * same side in a 9-corner league. Either club missing a rate falls back to
     * the league baseline for that side alone, rather than discarding both.
     *
     * @param  float|null  $homeFor  Home club's own count per game.
     * @param  float|null  $awayAgainst  Count the away club concedes per game.
     * @param  float  $leagueTotal  League average for the whole match.
     */
    public static function expectedTotal(
        ?float $homeFor,
        ?float $awayAgainst,
        ?float $awayFor,
        ?float $homeAgainst,
        float $leagueTotal,
    ): float {
        $leagueTotal = max(0.5, $leagueTotal);
        $perSide = $leagueTotal / 2.0;

        $home = self::side($homeFor, $awayAgainst, $perSide);
        $away = self::side($awayFor, $homeAgainst, $perSide);

        return $home + $away;
    }

    /**
     * One side's expected count: its own rate scaled by what the opponent allows.
     */
    protected static function side(?float $own, ?float $opponentConcedes, float $perSide): float
    {
        $attack = ($own !== null && $own > 0) ? $own / $perSide : 1.0;
        $defence = ($opponentConcedes !== null && $opponentConcedes > 0) ? $opponentConcedes / $perSide : 1.0;

        // Clamped: a three-match sample can produce a 3x strength that would
        // otherwise dominate the fixture.
        $strength = min(2.0, max(0.4, $attack * $defence));

        return $strength * $perSide;
    }

    /**
     * @return array<int, float>
     */
    protected static function poisson(float $mean): array
    {
        $probabilities = [];
        $current = exp(-$mean);

        for ($k = 0; $k <= self::MAX_COUNT; $k++) {
            $probabilities[$k] = $current;
            $current *= $mean / ($k + 1);
        }

        return $probabilities;
    }

    /**
     * Negative binomial matched to the given mean and variance.
     *
     * Built by recurrence rather than from the gamma function, which PHP has no
     * builtin for: P(0) = p^r, then P(k) = P(k-1) * (k+r-1)/k * (1-p). The
     * dispersion r is real-valued, which the recurrence handles and a
     * factorial-based binomial coefficient would not.
     *
     * @return array<int, float>
     */
    protected static function negativeBinomial(float $mean, float $variance): array
    {
        $r = ($mean * $mean) / ($variance - $mean);
        $p = $mean / $variance;

        // Guard the degenerate corner where variance barely exceeds the mean and
        // r explodes; Poisson is the limit of that case anyway.
        if (! is_finite($r) || $r <= 0.0 || $p <= 0.0 || $p >= 1.0) {
            return self::poisson($mean);
        }

        $probabilities = [];
        $current = $p ** $r;

        for ($k = 0; $k <= self::MAX_COUNT; $k++) {
            $probabilities[$k] = $current;
            $current *= (($k + $r) / ($k + 1)) * (1.0 - $p);
        }

        return $probabilities;
    }
}
