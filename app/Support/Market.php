<?php

namespace App\Support;

/**
 * One betting market the platform publishes a Top 10 list for.
 *
 * Markets used to be a three-string const echoed across a dozen files — the
 * enum column, four label helpers, two validation rules, the Claude schema,
 * the ranking jobs and three sets of hardcoded tabs. Adding a market meant
 * finding all of them, and missing one failed quietly. Everything now reads
 * from MarketRegistry, so a new market is a single entry.
 */
final class Market
{
    /** Graded against the full-time score, which every result carries. */
    public const FAMILY_FT_SCORE = 'ft_score';

    /** Graded against the half-time score. */
    public const FAMILY_HT_SCORE = 'ht_score';

    /** Graded against a match total that is not goals (corners, cards). */
    public const FAMILY_COUNT = 'count';

    /**
     * @param  string  $key  Stored in predictions.market and expert_picks.market.
     * @param  array<string, array<int, string>>  $outcomes  Canonical label => accepted spellings.
     * @param  array<int, string>  $requires  Grading-context keys this market needs beyond the full-time score.
     * @param  float|null  $threshold  Publication threshold; null defers to the global setting.
     * @param  bool  $generated  Whether the engine currently produces picks for it.
     * @param  bool  $listed  Whether it gets its own public Top 10 tab.
     */
    public function __construct(
        public readonly string $key,
        public readonly string $listLabel,
        public readonly string $label,
        public readonly string $short,
        public readonly array $outcomes,
        public readonly string $family,
        public readonly array $requires = [],
        public readonly ?float $line = null,
        public readonly ?float $threshold = null,
        public readonly bool $generated = true,
        public readonly bool $listed = true,
        public readonly ?string $pendingNote = null,
        public readonly bool $needsTeamStats = false,
    ) {}

    /**
     * The canonical outcome labels, in display order.
     *
     * @return array<int, string>
     */
    public function selections(): array
    {
        return array_keys($this->outcomes);
    }

    /**
     * Whether a result carrying these context keys can settle this market.
     *
     * A market whose data has not arrived is unsettleable, not lost — the same
     * distinction the grader already draws for unparseable expert picks.
     *
     * @param  array<string, mixed>  $context
     */
    public function isDeterminable(array $context): bool
    {
        foreach ($this->requires as $field) {
            if (! isset($context[$field]) || ! is_numeric($context[$field])) {
                return false;
            }
        }

        return true;
    }
}
