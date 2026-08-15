<?php

namespace App\Console\Commands;

use App\Models\GameMatch;
use App\Models\Result;
use App\Services\StatisticalPredictionService;
use App\Support\MarketOutcome;
use Illuminate\Console\Command;

/**
 * Re-run the model over finished fixtures and score it against what happened.
 *
 * The public track record answers "how have the picks we published done?".
 * This answers the earlier and more important question: "is the model's stated
 * confidence worth anything?" A model that says 70% and is right 70% of the
 * time is calibrated; one that says 70% and is right 52% of the time is selling
 * a number that means nothing, and the track record page would be advertising
 * it. The project doc asks for this validation before launch rather than after.
 *
 * Reported per market:
 *
 *   hit rate    — how often the tipped side won.
 *   base rate   — how often it would have won by always tipping the most common
 *                 outcome. A hit rate below this means the model is subtracting
 *                 value, however good the raw number looks.
 *   Brier score — mean squared error of the probability. Lower is better; the
 *                 skill score expresses it against the base rate, where 0 is no
 *                 better than guessing the base rate and 1 is perfect.
 *   log loss    — punishes confident mistakes far harder than Brier does.
 *   calibration — predicted vs actual within probability bands. This is the
 *                 table to read: consistent over-confidence shows up here long
 *                 before it shows up in the hit rate.
 *
 * Reads the form figures stored on each fixture, which the ingestion job writes
 * before kickoff. If fixtures have been backfilled with present-day form, the
 * result is optimistic — the model would be seeing data from after the match.
 */
class BacktestModelCommand extends Command
{
    protected $signature = 'model:backtest
                            {--from= : Only fixtures kicking off on or after this date (Y-m-d)}
                            {--to= : Only fixtures kicking off on or before this date (Y-m-d)}
                            {--league= : Restrict to one competition}
                            {--bins=5 : Calibration bands between 0 and 1}
                            {--min=30 : Refuse to report on fewer graded fixtures than this}';

    protected $description = 'Score the prediction model against historical results (calibration, Brier, log loss)';

    public function handle(StatisticalPredictionService $model): int
    {
        $query = Result::with('match')
            ->whereHas('match', function ($match) {
                if ($from = $this->option('from')) {
                    $match->where('kickoff_at', '>=', $from);
                }

                if ($to = $this->option('to')) {
                    $match->where('kickoff_at', '<=', $to);
                }

                if ($league = $this->option('league')) {
                    $match->where('league', $league);
                }
            });

        $stats = $this->emptyStats();
        $graded = 0;
        $skipped = 0;

        $query->chunkById(200, function ($results) use ($model, &$stats, &$graded, &$skipped) {
            foreach ($results as $result) {
                $match = $result->match;

                // Without form on both sides the engine falls back to league
                // average for both teams, which is not a prediction — scoring
                // it would flatter the model with a coin flip.
                if (! $match || ! $this->hasUsableForm($match)) {
                    $skipped++;

                    continue;
                }

                $predictions = $model->generate($match);
                $actual = MarketOutcome::actual($result->home_score, $result->away_score);

                foreach (MarketOutcome::MARKETS as $market) {
                    if (! isset($predictions[$market])) {
                        continue;
                    }

                    $pick = $predictions[$market]['pick'];
                    $probability = (float) $predictions[$market]['probability'];
                    $won = MarketOutcome::normalise($market, $pick) === $actual[$market];

                    $this->tally($stats[$market], $probability, $won, $actual[$market]);
                }

                $graded++;
            }
        });

        if ($graded === 0) {
            $this->error('No fixtures with both a result and usable form data. Ingest more history before trusting the model.');

            return self::FAILURE;
        }

        $this->line('');
        $this->info("Backtested {$graded} fixture(s).");

        if ($skipped) {
            $this->line("Skipped {$skipped} without usable form.");
        }

        $minimum = (int) $this->option('min');

        if ($graded < $minimum) {
            $this->warn("Fewer than {$minimum} fixtures: the figures below are noise, not evidence. Treat as a smoke test only.");
        }

        foreach (MarketOutcome::MARKETS as $market) {
            $this->reportMarket($market, $stats[$market]);
        }

        $this->line('');
        $this->line('<comment>A skill score at or below 0 means the model is no better than always tipping the most common outcome.</comment>');

        return self::SUCCESS;
    }

    /**
     * Does this fixture carry the goals-for/against figures the engine needs?
     */
    protected function hasUsableForm(GameMatch $match): bool
    {
        $home = is_array($match->home_form) ? $match->home_form : [];
        $away = is_array($match->away_form) ? $match->away_form : [];

        return isset($home['gf'], $home['ga'], $away['gf'], $away['ga']);
    }

    /**
     * @param  array<string, mixed>  $bucket
     */
    protected function tally(array &$bucket, float $probability, bool $won, string $actualOutcome): void
    {
        $probability = min(max($probability, 0.0), 1.0);

        $bucket['n']++;
        $bucket['wins'] += $won ? 1 : 0;
        $bucket['probability_sum'] += $probability;

        // Brier and log loss over the tipped side: outcome is 1 when the tip
        // landed, so both measure the probability attached to the actual event.
        $outcome = $won ? 1.0 : 0.0;
        $bucket['brier_sum'] += ($probability - $outcome) ** 2;

        // Clamped so a confident miss is a large finite penalty rather than
        // infinity, which would make the whole run unreportable.
        $clamped = min(max($probability, 1e-6), 1 - 1e-6);
        $bucket['log_loss_sum'] += -($outcome * log($clamped) + (1 - $outcome) * log(1 - $clamped));

        $bins = max(2, (int) $this->option('bins'));
        $index = min($bins - 1, (int) floor($probability * $bins));
        $bucket['bins'][$index]['n']++;
        $bucket['bins'][$index]['wins'] += $won ? 1 : 0;
        $bucket['bins'][$index]['probability_sum'] += $probability;

        $bucket['outcomes'][$actualOutcome] = ($bucket['outcomes'][$actualOutcome] ?? 0) + 1;
    }

    /**
     * @param  array<string, mixed>  $bucket
     */
    protected function reportMarket(string $market, array $bucket): void
    {
        $this->line('');
        $this->line('<options=bold>'.strtoupper(str_replace('_', ' ', $market)).'</>');

        if ($bucket['n'] === 0) {
            $this->line('  No graded picks.');

            return;
        }

        $n = $bucket['n'];
        $hitRate = $bucket['wins'] / $n;
        $meanProbability = $bucket['probability_sum'] / $n;
        $brier = $bucket['brier_sum'] / $n;
        $logLoss = $bucket['log_loss_sum'] / $n;

        // Always tipping the most frequent outcome — the bar any model has to clear.
        $baseRate = $bucket['outcomes'] ? max($bucket['outcomes']) / $n : 0.0;
        $baselineBrier = $baseRate * (1 - $baseRate);
        $skill = $baselineBrier > 0 ? 1 - ($brier / $baselineBrier) : 0.0;

        $this->table(
            ['picks', 'hit rate', 'base rate', 'mean confidence', 'brier', 'skill', 'log loss'],
            [[
                $n,
                $this->percent($hitRate),
                $this->percent($baseRate),
                $this->percent($meanProbability),
                number_format($brier, 4),
                number_format($skill, 3),
                number_format($logLoss, 4),
            ]]
        );

        // Confidence that runs ahead of the hit rate is the failure mode that
        // matters here: it is what turns a published "78% confidence" into a
        // number the track record cannot support.
        if ($meanProbability - $hitRate > 0.05) {
            $this->warn(sprintf(
                '  Over-confident by %s: claims %s, delivers %s.',
                $this->percent($meanProbability - $hitRate),
                $this->percent($meanProbability),
                $this->percent($hitRate),
            ));
        }

        if ($hitRate < $baseRate) {
            $this->warn('  Below the base rate — always tipping the most common outcome would have done better.');
        }

        $rows = [];

        foreach ($bucket['bins'] as $index => $bin) {
            if ($bin['n'] === 0) {
                continue;
            }

            $bins = count($bucket['bins']);
            $rows[] = [
                sprintf('%d–%d%%', (int) ($index / $bins * 100), (int) (($index + 1) / $bins * 100)),
                $bin['n'],
                $this->percent($bin['probability_sum'] / $bin['n']),
                $this->percent($bin['wins'] / $bin['n']),
            ];
        }

        if ($rows) {
            $this->table(['band', 'picks', 'predicted', 'actual'], $rows);
        }
    }

    protected function percent(float $value): string
    {
        return number_format($value * 100, 1).'%';
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function emptyStats(): array
    {
        $bins = max(2, (int) $this->option('bins'));

        $bucket = [
            'n' => 0,
            'wins' => 0,
            'probability_sum' => 0.0,
            'brier_sum' => 0.0,
            'log_loss_sum' => 0.0,
            'outcomes' => [],
            'bins' => array_fill(0, $bins, ['n' => 0, 'wins' => 0, 'probability_sum' => 0.0]),
        ];

        return array_fill_keys(MarketOutcome::MARKETS, $bucket);
    }
}
