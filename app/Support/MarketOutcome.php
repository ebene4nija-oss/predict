<?php

namespace App\Support;

/**
 * Single source of truth for grading a pick against a result.
 *
 * Grading logic used to live in three places that disagreed with each other:
 * the track record scored a "both teams to score" pick as "GG (Yes)" while the
 * expert leaderboard scored the same pick as "Yes", so the same pick could be
 * a win on one page and a loss on another. Everything now normalises through
 * here, which also tolerates the free-text picks experts submit.
 *
 * Three states matter and are easy to conflate:
 *
 *  - the market is absent from actual() — the data needed to settle it has not
 *    arrived, so the pick is unverifiable and must not be counted either way;
 *  - the market maps to null — the result is known and no selection won (a
 *    draw on a win-only market), so every pick in it loses;
 *  - the market maps to a label — the pick wins if it normalises to that label.
 */
class MarketOutcome
{
    /**
     * The outcome of a match, keyed by market, in canonical form.
     *
     * Markets whose grading data is missing are omitted rather than guessed.
     *
     * @param  array<string, mixed>  $context  Extra settle data: ht_home, ht_away, corners, cards.
     * @return array<string, string|null>
     */
    public static function actual(int $homeScore, int $awayScore, array $context = []): array
    {
        $outcomes = [];

        foreach (MarketRegistry::all() as $key => $market) {
            if (! $market->isDeterminable($context)) {
                continue;
            }

            $outcomes[$key] = self::resolve($market, $homeScore, $awayScore, $context);
        }

        return $outcomes;
    }

    /**
     * Whether this result carries the data needed to settle this market.
     *
     * @param  array<string, mixed>  $context
     */
    public static function isDeterminable(string $market, array $context = []): bool
    {
        return (bool) MarketRegistry::find($market)?->isDeterminable($context);
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
     * Did this pick win, given the result?
     *
     * @param  array<string, mixed>  $context
     */
    public static function isWinningPick(
        string $market,
        ?string $pick,
        int $homeScore,
        int $awayScore,
        array $context = [],
    ): bool {
        $normalised = static::normalise($market, $pick);

        if ($normalised === null) {
            return false;
        }

        $actual = static::actual($homeScore, $awayScore, $context);

        // Absent means unsettleable and null means nothing won; neither is a
        // win, and callers separate the two via isDeterminable().
        return isset($actual[$market]) && $normalised === $actual[$market];
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

        foreach (MarketRegistry::find($market)?->outcomes ?? [] as $canonical => $accepted) {
            if (in_array($value, $accepted, true)) {
                return $canonical;
            }
        }

        return null;
    }

    /**
     * The canonical outcome of one market.
     *
     * @param  array<string, mixed>  $context
     */
    protected static function resolve(Market $market, int $homeScore, int $awayScore, array $context): ?string
    {
        // Two-outcome markets list the affirmative selection first, so the
        // labels come from the registry rather than being restated here.
        $selections = $market->selections();
        $first = $selections[0] ?? null;
        $second = $selections[1] ?? null;

        return match ($market->family) {
            Market::FAMILY_FT_SCORE => match ($market->key) {
                'win_draw_loss' => $homeScore > $awayScore
                    ? 'Home Win'
                    : ($homeScore === $awayScore ? 'Draw' : 'Away Win'),
                // A draw settles the market without settling any selection.
                'win' => $homeScore > $awayScore ? $first : ($homeScore < $awayScore ? $second : null),
                'gg' => ($homeScore > 0 && $awayScore > 0) ? $first : $second,
                default => $market->line === null
                    ? null
                    : (($homeScore + $awayScore) > $market->line ? $first : $second),
            },

            Market::FAMILY_HT_SCORE => self::resolveHalfTime($market, $context, $first, $second),

            Market::FAMILY_COUNT => $market->line === null || $market->requires === []
                ? null
                : (((float) $context[$market->requires[0]]) > $market->line ? $first : $second),

            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected static function resolveHalfTime(Market $market, array $context, ?string $first, ?string $second): ?string
    {
        $home = (int) $context['ht_home'];
        $away = (int) $context['ht_away'];

        if ($market->line !== null) {
            return ($home + $away) > $market->line ? $first : $second;
        }

        // Level at the break settles the market with no winning selection.
        return $home > $away ? $first : ($home < $away ? $second : null);
    }
}
