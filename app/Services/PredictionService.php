<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\Prediction;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

/**
 * Chooses a provider, persists the result, and locks the pick.
 *
 * Two rules this class exists to enforce:
 *
 * 1. Stored probabilities are the model's real estimate. The confidence
 *    threshold decides which picks get *promoted* (see RankingJob) — it never
 *    rewrites a probability upward, which is what the previous implementation
 *    did via max($minConfidence, $p).
 * 2. A pick is locked at `published_at` and never rewritten once its fixture
 *    has kicked off, so the public track record cannot be edited after the
 *    outcome is known.
 */
class PredictionService
{
    public function __construct(
        protected ClaudePredictionService $claude,
        protected StatisticalPredictionService $statistical,
    ) {}

    /**
     * Generate and store predictions for a fixture.
     *
     * @return array<string, Prediction>
     */
    public function calculateAndStore(GameMatch $match): array
    {
        if ($this->isLocked($match)) {
            Log::info('Skipped prediction refresh for a started fixture', ['match_id' => $match->id]);

            return [];
        }

        [$markets, $source] = $this->analyse($match);

        $stored = [];

        foreach ($markets as $market => $data) {
            $stored[$market] = Prediction::updateOrCreate(
                ['match_id' => $match->id, 'market' => $market],
                [
                    'pick' => $data['pick'],
                    'probability' => round($data['probability'], 4),
                    'rationale' => $data['rationale'],
                    'source' => $source,
                    'published_at' => now(),
                ]
            );
        }

        return $stored;
    }

    /**
     * Claude when configured and healthy, the statistical model otherwise.
     *
     * @return array{0: array<string, array{pick: string, probability: float, rationale: string}>, 1: string}
     */
    protected function analyse(GameMatch $match): array
    {
        $provider = (string) Setting::get('prediction_provider', 'claude');

        if ($provider === 'claude' && $this->claude->isConfigured()) {
            $markets = $this->claude->generate($match);

            if ($markets !== null) {
                return [$markets, 'claude'];
            }

            // generate() has already logged why; recorded here so the stored
            // row shows the pick did not come from the advertised engine.
            return [$this->statistical->generate($match), 'poisson_fallback'];
        }

        return [$this->statistical->generate($match), 'poisson_xg'];
    }

    /**
     * A fixture that has kicked off no longer accepts prediction changes.
     */
    public function isLocked(GameMatch $match): bool
    {
        return $match->kickoff_at !== null && $match->kickoff_at->isPast();
    }

    /**
     * The probability a pick must reach before it can be promoted to the
     * public Top 10 / AI Top 5 lists.
     */
    public static function publishThreshold(): float
    {
        return (float) Setting::get('min_confidence_threshold', 0.55);
    }
}
