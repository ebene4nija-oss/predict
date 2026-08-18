<?php

namespace App\Support;

use App\Models\Setting;

/**
 * The catalogue of markets the platform knows about.
 *
 * Seven of these are public Top 10 lists. `win_draw_loss` is deliberately not
 * one: it predates the lists, carries the existing track record and is what
 * experts submit against, so it keeps being generated and shown on match pages
 * without claiming a tab of its own.
 *
 * Markets flagged `generated: false` are registered but not yet produced — the
 * tab renders as pending rather than as an empty list, and nothing downstream
 * has to be edited when the data source that unblocks them lands.
 */
final class MarketRegistry
{
    /** Shown when no market is requested. */
    public const DEFAULT_KEY = 'win';

    /** @var array<string, Market>|null */
    protected static ?array $markets = null;

    /**
     * @return array<string, Market>
     */
    public static function all(): array
    {
        return self::$markets ??= self::define();
    }

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    /**
     * Markets the engine currently produces picks for.
     *
     * @return array<string, Market>
     */
    public static function generated(): array
    {
        return array_filter(self::all(), fn (Market $m) => $m->generated);
    }

    /**
     * @return array<int, string>
     */
    public static function generatedKeys(): array
    {
        return array_keys(self::generated());
    }

    /**
     * Markets that can be produced for a specific fixture.
     *
     * @return array<string, Market>
     */
    public static function generatedFor(bool $hasTeamStats = true): array
    {
        return self::generated();
    }

    /**
     * Markets that get a public Top 10 tab, in display order.
     *
     * @return array<string, Market>
     */
    public static function listed(): array
    {
        return array_filter(self::all(), fn (Market $m) => $m->listed);
    }

    /**
     * Markets graded purely from the full-time score.
     *
     * @return array<string, Market>
     */
    public static function byFamily(string $family): array
    {
        return array_filter(self::all(), fn (Market $m) => $m->family === $family);
    }

    public static function find(string $key): ?Market
    {
        return self::all()[$key] ?? null;
    }

    public static function has(string $key): bool
    {
        return isset(self::all()[$key]);
    }

    /**
     * Resolve a requested market key to one that can actually be displayed.
     */
    public static function resolve(?string $key): string
    {
        $market = $key === null ? null : self::find($key);

        return $market && $market->listed ? $market->key : self::DEFAULT_KEY;
    }

    /**
     * The probability a pick in this market must reach to be promoted.
     *
     * A single global threshold cannot serve markets whose natural range
     * differs by 40 points: 0.55 would publish every first-half over and no
     * half-time win at all. Resolution order is per-market setting, then the
     * market's own default, then the global setting.
     */
    public static function threshold(string $key): float
    {
        $override = Setting::get("min_confidence_threshold.{$key}");

        if (is_numeric($override)) {
            return (float) $override;
        }

        $market = self::find($key);

        if ($market?->threshold !== null) {
            return $market->threshold;
        }

        return (float) Setting::get('min_confidence_threshold', 0.55);
    }

    /**
     * Used by tests to pick up settings changed mid-run.
     */
    public static function flush(): void
    {
        self::$markets = null;
    }

    /**
     * @return array<string, Market>
     */
    protected static function define(): array
    {
        $markets = [
            new Market(
                key: 'win',
                listLabel: 'Top 10 Win',
                label: 'Match Winner',
                short: 'WIN',
                outcomes: [
                    'Home Win' => ['home win', 'home', '1', 'h'],
                    'Away Win' => ['away win', 'away', '2', 'a'],
                ],
                family: Market::FAMILY_FT_SCORE,
            ),

            new Market(
                key: 'over_2_5',
                listLabel: 'Top 10 Over 2.5',
                label: 'Over 2.5 Goals',
                short: 'O2.5',
                outcomes: [
                    'Over 2.5' => ['over 2.5', 'over', 'o2.5', 'over 2,5'],
                    'Under 2.5' => ['under 2.5', 'under', 'u2.5', 'under 2,5'],
                ],
                family: Market::FAMILY_FT_SCORE,
                line: 2.5,
            ),

            new Market(
                key: 'gg',
                listLabel: 'Top 10 BTTS',
                label: 'Both Teams To Score (GG)',
                short: 'GG',
                outcomes: [
                    'GG (Yes)' => ['gg (yes)', 'gg', 'yes', 'btts', 'btts yes', 'both teams to score'],
                    'NG (No)' => ['ng (no)', 'ng', 'no', 'btts no'],
                ],
                family: Market::FAMILY_FT_SCORE,
            ),

            // Half-time markets, modelled from the same expected goals as the
            // full-time ones and graded against the half-time score.
            new Market(
                key: 'fh_over_0_5',
                listLabel: 'Top 10 1H Over 0.5',
                label: 'First Half Over 0.5 Goals',
                short: '1H O0.5',
                outcomes: [
                    '1H Over 0.5' => ['1h over 0.5', 'first half over 0.5', 'over 0.5', 'o0.5', 'fh over 0.5'],
                    '1H Under 0.5' => ['1h under 0.5', 'first half under 0.5', 'under 0.5', 'u0.5', 'fh under 0.5'],
                ],
                family: Market::FAMILY_HT_SCORE,
                requires: ['ht_home', 'ht_away'],
                line: 0.5,
                // Roughly three in four first halves produce a goal, so the
                // global 0.55 would promote almost the whole board.
                threshold: 0.70,
            ),

            new Market(
                key: 'ht_win',
                listLabel: 'Top 10 HT Win',
                label: 'Leading At Half Time',
                short: 'HT WIN',
                outcomes: [
                    'HT Home Win' => ['ht home win', 'ht home', 'home', 'half time home', '1'],
                    'HT Away Win' => ['ht away win', 'ht away', 'away', 'half time away', '2'],
                ],
                family: Market::FAMILY_HT_SCORE,
                requires: ['ht_home', 'ht_away'],
                // Leading at the break rarely clears 50% even for strong
                // favourites; the global threshold would publish nothing.
                threshold: 0.40,
            ),

            // Count markets, modelled and settled from the football-data.co.uk
            // season CSVs. Both need per-club rates, so a fixture involving a
            // club from an uncovered competition gets neither.
            new Market(
                key: 'corners_over_8_5',
                listLabel: 'Top 10 Corners',
                label: 'Over 8.5 Match Corners',
                short: 'COR',
                outcomes: [
                    'Over 8.5 Corners' => ['over 8.5 corners', 'over 8.5', 'o8.5', 'corners over'],
                    'Under 8.5 Corners' => ['under 8.5 corners', 'under 8.5', 'u8.5', 'corners under'],
                ],
                family: Market::FAMILY_COUNT,
                requires: ['corners'],
                line: 8.5,
                // Measured at 51%–66% across the covered leagues last season,
                // so this genuinely selects rather than listing near-certainties.
                threshold: 0.60,
                needsTeamStats: true,
            ),

            new Market(
                key: 'cards_over_2_5',
                listLabel: 'Top 10 Cards',
                label: 'Over 2.5 Match Yellow Cards',
                short: 'CRD',
                outcomes: [
                    'Over 2.5 Cards' => ['over 2.5 cards', 'over 2.5 yellows', 'o2.5 cards', 'cards over'],
                    'Under 2.5 Cards' => ['under 2.5 cards', 'under 2.5 yellows', 'u2.5 cards', 'cards under'],
                ],
                family: Market::FAMILY_COUNT,
                requires: ['cards'],
                line: 2.5,
                // 71%–79% across the covered leagues; the 0.55 global threshold
                // would promote essentially the whole board.
                threshold: 0.75,
                needsTeamStats: true,
            ),

            // Not a Top 10 list: it predates them, holds the existing track
            // record, and is the market experts submit against.
            new Market(
                key: 'win_draw_loss',
                listLabel: 'Match Outcome',
                label: 'Match Outcome (W/D/L)',
                short: 'WDL',
                outcomes: [
                    'Home Win' => ['home win', 'home', '1', 'h'],
                    'Draw' => ['draw', 'x', 'tie'],
                    'Away Win' => ['away win', 'away', '2', 'a'],
                ],
                family: Market::FAMILY_FT_SCORE,
                listed: false,
            ),
        ];

        $keyed = [];

        foreach ($markets as $market) {
            $keyed[$market->key] = $market;
        }

        return $keyed;
    }
}
