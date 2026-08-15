<?php

namespace App\Support;

/**
 * Single source of truth for grading a pick against a final score.
 *
 * Grading logic used to live in three places that disagreed with each other:
 * the track record scored a "both teams to score" pick as "GG (Yes)" while the
 * expert leaderboard scored the same pick as "Yes", so the same pick could be
 * a win on one page and a loss on another. Everything now normalises through
 * here, which also tolerates the free-text picks experts submit.
 */
class MarketOutcome
{
    public const MARKETS = ['win_draw_loss', 'gg', 'over_2_5'];

    /**
     * The actual outcome of a match, keyed by market, in canonical form.
     *
     * @return array{win_draw_loss: string, gg: string, over_2_5: string}
     */
    public static function actual(int $homeScore, int $awayScore): array
    {
        return [
            'win_draw_loss' => $homeScore > $awayScore
                ? 'Home Win'
                : ($homeScore === $awayScore ? 'Draw' : 'Away Win'),
            'gg' => ($homeScore > 0 && $awayScore > 0) ? 'GG (Yes)' : 'NG (No)',
            'over_2_5' => ($homeScore + $awayScore) > 2.5 ? 'Over 2.5' : 'Under 2.5',
        ];
    }

    /**
     * Can this pick be graded at all?
     *
     * Experts submit free text, so a pick like "Home or Draw" maps onto no
     * canonical outcome. Such a pick is not a loss — it is unscoreable, and
     * counting it as a loss would understate the tipster it belongs to.
     */
    public static function isGradeable(string $market, ?string $pick): bool
    {
        return static::normalise($market, $pick) !== null;
    }

    /**
     * Did this pick win, given the final score?
     */
    public static function isWinningPick(string $market, ?string $pick, int $homeScore, int $awayScore): bool
    {
        $normalised = static::normalise($market, $pick);

        if ($normalised === null) {
            return false;
        }

        return $normalised === (static::actual($homeScore, $awayScore)[$market] ?? null);
    }

    /**
     * Map the many ways a pick gets written down onto its canonical label.
     */
    public static function normalise(string $market, ?string $pick): ?string
    {
        $value = strtolower(trim((string) $pick));

        if ($value === '') {
            return null;
        }

        $aliases = match ($market) {
            'win_draw_loss' => [
                'Home Win' => ['home win', 'home', '1', 'h'],
                'Away Win' => ['away win', 'away', '2', 'a'],
                'Draw' => ['draw', 'x', 'tie'],
            ],
            'gg' => [
                'GG (Yes)' => ['gg (yes)', 'gg', 'yes', 'btts', 'btts yes', 'both teams to score'],
                'NG (No)' => ['ng (no)', 'ng', 'no', 'btts no'],
            ],
            'over_2_5' => [
                'Over 2.5' => ['over 2.5', 'over', 'o2.5', 'over 2,5'],
                'Under 2.5' => ['under 2.5', 'under', 'u2.5', 'under 2,5'],
            ],
            default => [],
        };

        foreach ($aliases as $canonical => $accepted) {
            if (in_array($value, $accepted, true)) {
                return $canonical;
            }
        }

        return null;
    }
}
