<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\Setting;
use App\Support\MarketOutcome;
use App\Support\MarketRegistry;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Moonshot Kimi-backed match analysis.
 *
 * Calls Moonshot AI API with JSON output mode to estimate market probabilities
 * and picks from the fixture evidence.
 */
class KimiPredictionService
{
    public const DEFAULT_MODEL = 'moonshot-v1-8k';

    public function isConfigured(): bool
    {
        return $this->apiKey() !== '';
    }

    protected function apiKey(): string
    {
        $key = Setting::credential('kimi_api_key', 'services.kimi.key');

        return str_contains(strtoupper($key), 'MOCK') ? '' : $key;
    }

    public function model(): string
    {
        return Setting::credential('kimi_model', 'services.kimi.model') ?: self::DEFAULT_MODEL;
    }

    /**
     * Analyse a fixture.
     *
     * @return array<string, array{pick: string, probability: float, rationale: string}>|null
     */
    public function generate(GameMatch $match): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $markets = MarketRegistry::generatedFor($match->hasCountStats());

        try {
            $response = Http::withToken($this->apiKey())
                ->timeout(30)
                ->post('https://api.moonshot.cn/v1/chat/completions', [
                    'model' => $this->model(),
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a quantitative football analyst. Estimate genuine probabilities '
                                .'from the evidence supplied. Do not inflate confidence: if a market is close '
                                .'to a coin flip, say so. Probabilities are decimals between 0 and 1. '
                                .'Return a JSON object where each key is the market name and the value contains pick, probability, and rationale.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $this->prompt($match, $markets),
                        ],
                    ],
                    'response_format' => [
                        'type' => 'json_object',
                    ],
                ]);

            if (! $response->successful()) {
                Log::error('Kimi prediction request failed', [
                    'match_id' => $match->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $content = $response->json('choices.0.message.content');
            if (empty($content)) {
                Log::warning('Kimi returned empty prediction content', ['match_id' => $match->id]);

                return null;
            }

            $payload = is_array($content) ? $content : json_decode($content, true);
            if (! is_array($payload)) {
                Log::warning('Kimi returned non-JSON payload', ['match_id' => $match->id, 'content' => $content]);

                return null;
            }

            return $this->normalise($payload, $match, array_keys($markets));
        } catch (\Throwable $e) {
            Log::error('Kimi prediction transport failure', [
                'match_id' => $match->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Validate and clamp the model's output.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $expected
     * @return array<string, array{pick: string, probability: float, rationale: string}>|null
     */
    protected function normalise(array $payload, GameMatch $match, array $expected): ?array
    {
        $markets = [];

        foreach ($expected as $market) {
            $entry = $payload[$market] ?? null;

            if (! is_array($entry)) {
                continue;
            }

            $pick = MarketOutcome::normalise($market, $entry['pick'] ?? null);
            $probability = $entry['probability'] ?? null;

            if ($pick === null || ! is_numeric($probability)) {
                continue;
            }

            $probability = (float) $probability;

            if ($probability <= 0.0 || $probability > 1.0) {
                Log::warning('Kimi returned an out-of-range probability', [
                    'match_id' => $match->id,
                    'market' => $market,
                    'probability' => $probability,
                ]);

                continue;
            }

            $markets[$market] = [
                'pick' => $pick,
                'probability' => $probability,
                'rationale' => trim((string) ($entry['rationale'] ?? '')) ?: 'Kimi analysis.',
            ];
        }

        if ($markets === []) {
            Log::warning('Kimi response contained no usable markets', ['match_id' => $match->id]);

            return null;
        }

        return $markets;
    }

    /**
     * @param  array<string, \App\Support\Market>  $markets
     */
    protected function prompt(GameMatch $match, array $markets): string
    {
        $kickoff = $match->kickoff_at?->toDayDateTimeString() ?? 'unknown';

        $instructions = [];

        foreach ($markets as $key => $market) {
            $picks = implode(', ', array_map(fn ($p) => "\"{$p}\"", $market->selections()));
            $instructions[] = "- {$key} ({$market->label}): pick one of {$picks}";
        }

        return implode("\n", [
            "Fixture: {$match->home_team} (home) vs {$match->away_team} (away)",
            "Competition: {$match->league}",
            "Kickoff: {$kickoff}",
            'Home form (goals for/against per game): '.json_encode($match->home_form),
            'Away form (goals for/against per game): '.json_encode($match->away_form),
            'Head to head: '.($match->h2h_summary ?: 'not supplied'),
            'Team news: '.($match->injury_notes ?: 'not supplied'),
            '',
            'Estimate the probability of your selected outcome in each of '.count($markets).' markets:',
            ...$instructions,
            '',
            'The probability is that of the outcome you picked, not of the favourite.',
            'Keep the markets mutually consistent: they describe the same match.',
            'Keep each rationale under 200 characters.',
        ]);
    }
}
