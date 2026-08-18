<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\Prediction;
use App\Models\Setting;
use App\Support\MarketRegistry;
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
        protected OpenAiPredictionService $openai,
        protected GeminiPredictionService $gemini,
        protected KimiPredictionService $kimi,
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
     * Selected AI model when configured and healthy, the statistical model otherwise.
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

            return [$this->statistical->generate($match), 'poisson_fallback'];
        }

        if (in_array($provider, ['chatgpt', 'openai'], true) && $this->openai->isConfigured()) {
            $markets = $this->openai->generate($match);

            if ($markets !== null) {
                return [$markets, 'chatgpt'];
            }

            return [$this->statistical->generate($match), 'poisson_fallback'];
        }

        if ($provider === 'gemini' && $this->gemini->isConfigured()) {
            $markets = $this->gemini->generate($match);

            if ($markets !== null) {
                return [$markets, 'gemini'];
            }

            return [$this->statistical->generate($match), 'poisson_fallback'];
        }

        if ($provider === 'kimi' && $this->kimi->isConfigured()) {
            $markets = $this->kimi->generate($match);

            if ($markets !== null) {
                return [$markets, 'kimi'];
            }

            return [$this->statistical->generate($match), 'poisson_fallback'];
        }

        if ($provider !== 'poisson_xg') {
            // Selected provider was not configured or unrecognized; log and fallback
            Log::info('Prediction provider unconfigured or unavailable, using Poisson fallback', [
                'provider' => $provider,
                'match_id' => $match->id,
            ]);

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
     *
     * Resolved per market: markets differ in natural range by up to forty
     * points, so one global figure would publish an entire market or none of
     * it. See MarketRegistry::threshold().
     */
    public static function publishThreshold(?string $market = null): float
    {
        return $market === null
            ? (float) Setting::get('min_confidence_threshold', 0.55)
            : MarketRegistry::threshold($market);
    }
}
