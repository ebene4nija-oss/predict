<?php

namespace App\Services;

use App\Models\Result;
use App\Support\MarketOutcome;
use Illuminate\Support\Facades\Cache;

class TrackRecordService
{
    public const CACHE_KEY = 'track_record.accuracy_stats';
    public const CACHE_TTL_SECONDS = 900;

    /** Probability bands used for the reliability breakdown. */
    protected const CALIBRATION_BUCKETS = [
        [0.50, 0.60],
        [0.60, 0.70],
        [0.70, 0.80],
        [0.80, 0.90],
        [0.90, 1.01],
    ];

    /**
     * Published accuracy statistics for AI and experts, by market.
     *
     * Cached because the home page and every match page render it; the cache
     * is flushed whenever a result is settled.
     */
    public function getAccuracyStats(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, fn () => $this->computeAccuracyStats());
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected function computeAccuracyStats(): array
    {
        $aiStats = $this->emptyStats();
        $expertStats = $this->emptyStats();
        $calibration = $this->emptyCalibration();

        Result::with(['match.predictions', 'match.expertPicks'])
            ->chunkById(200, function ($results) use (&$aiStats, &$expertStats, &$calibration) {
                foreach ($results as $result) {
                    $match = $result->match;

                    if (! $match) {
                        continue;
                    }

                    $home = $result->home_score;
                    $away = $result->away_score;

                    foreach ($match->predictions as $prediction) {
                        // Only picks locked before kickoff count. A prediction
                        // written or edited after the whistle proves nothing,
                        // so it is excluded from the published record instead
                        // of quietly inflating it.
                        if (! $this->wasPublishedBeforeKickoff($prediction, $match)) {
                            continue;
                        }

                        // A pick that maps onto no outcome cannot be scored, and
                        // scoring it as a loss would misreport the record.
                        if (! MarketOutcome::isGradeable($prediction->market, $prediction->pick)) {
                            continue;
                        }

                        $won = MarketOutcome::isWinningPick($prediction->market, $prediction->pick, $home, $away);

                        $this->tally($aiStats, $prediction->market, $won, $prediction->profitUnits($won));
                        $this->tallyCalibration($calibration, (float) $prediction->probability, $won);
                    }

                    foreach ($match->expertPicks as $pick) {
                        // Experts type their picks freehand, so this is where
                        // unscoreable entries actually show up.
                        if (! MarketOutcome::isGradeable($pick->market, $pick->pick)) {
                            continue;
                        }

                        $won = MarketOutcome::isWinningPick($pick->market, $pick->pick, $home, $away);

                        $this->tally($expertStats, $pick->market, $won, null);
                    }
                }
            });

        return [
            'ai' => $this->withDerivedFigures($aiStats),
            'expert' => $this->withDerivedFigures($expertStats),
            'calibration' => $this->withCalibrationRates($calibration),
        ];
    }

    /**
     * A pick counts only if it was locked before the fixture started.
     */
    protected function wasPublishedBeforeKickoff($prediction, $match): bool
    {
        if ($prediction->published_at === null || $match->kickoff_at === null) {
            // Rows predating the audit trail cannot be verified either way;
            // treat them as unverifiable rather than assume good faith.
            return false;
        }

        return $prediction->published_at->lessThanOrEqualTo($match->kickoff_at);
    }

    protected function tally(array &$stats, string $market, bool $won, ?float $profitUnits): void
    {
        if (! isset($stats[$market])) {
            return;
        }

        foreach ([$market, 'overall'] as $bucket) {
            $stats[$bucket]['total']++;

            if ($won) {
                $stats[$bucket]['won']++;
            }

            // Staked/returned are tracked only across priced picks, so ROI is
            // never diluted by picks we have no odds for.
            if ($profitUnits !== null) {
                $stats[$bucket]['staked']++;
                $stats[$bucket]['profit'] += $profitUnits;
            }
        }
    }

    protected function tallyCalibration(array &$calibration, float $probability, bool $won): void
    {
        foreach (self::CALIBRATION_BUCKETS as $index => [$low, $high]) {
            if ($probability >= $low && $probability < $high) {
                $calibration['buckets'][$index]['total']++;

                if ($won) {
                    $calibration['buckets'][$index]['won']++;
                }

                break;
            }
        }

        // Brier score: mean squared error of the probability against the
        // outcome. Lower is better; this is what shows whether a stated 70%
        // actually means 70%, which hit rate alone cannot.
        $calibration['brier_sum'] += ($probability - ($won ? 1.0 : 0.0)) ** 2;
        $calibration['brier_count']++;
    }

    protected function emptyStats(): array
    {
        $stats = [];

        foreach ([...MarketOutcome::MARKETS, 'overall'] as $key) {
            $stats[$key] = [
                'total' => 0,
                'won' => 0,
                'rate' => 0.0,
                'staked' => 0,
                'profit' => 0.0,
                'roi' => null,
            ];
        }

        return $stats;
    }

    protected function emptyCalibration(): array
    {
        $buckets = [];

        foreach (self::CALIBRATION_BUCKETS as [$low, $high]) {
            $buckets[] = [
                'label' => round($low * 100).'–'.round(min($high, 1.0) * 100).'%',
                'expected' => round((($low + min($high, 1.0)) / 2) * 100, 1),
                'total' => 0,
                'won' => 0,
                'actual' => null,
            ];
        }

        return ['buckets' => $buckets, 'brier_sum' => 0.0, 'brier_count' => 0];
    }

    protected function withDerivedFigures(array $stats): array
    {
        foreach ($stats as $key => $bucket) {
            $stats[$key]['rate'] = $bucket['total'] > 0
                ? round(($bucket['won'] / $bucket['total']) * 100, 1)
                : 0.0;

            // Return on investment across priced picks, in percent of turnover.
            // Hit rate without this is not a claim about profitability.
            $stats[$key]['roi'] = $bucket['staked'] > 0
                ? round(($bucket['profit'] / $bucket['staked']) * 100, 1)
                : null;

            $stats[$key]['profit'] = round($bucket['profit'], 2);
        }

        return $stats;
    }

    protected function withCalibrationRates(array $calibration): array
    {
        foreach ($calibration['buckets'] as $index => $bucket) {
            $calibration['buckets'][$index]['actual'] = $bucket['total'] > 0
                ? round(($bucket['won'] / $bucket['total']) * 100, 1)
                : null;
        }

        $calibration['brier'] = $calibration['brier_count'] > 0
            ? round($calibration['brier_sum'] / $calibration['brier_count'], 4)
            : null;

        $calibration['sample'] = $calibration['brier_count'];

        unset($calibration['brier_sum'], $calibration['brier_count']);

        return $calibration;
    }
}
