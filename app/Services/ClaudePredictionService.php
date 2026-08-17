<?php

namespace App\Services;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\AnthropicException;
use Anthropic\Messages\JSONOutputFormat;
use Anthropic\Messages\OutputConfig;
use App\Models\GameMatch;
use App\Models\Setting;
use App\Support\MarketOutcome;
use App\Support\MarketRegistry;
use Illuminate\Support\Facades\Log;

/**
 * Claude-backed match analysis.
 *
 * Returns normalised market probabilities or null; persistence and the
 * publishing threshold are the caller's business (see PredictionService).
 * A null return means "Claude was unavailable or unusable" and the caller
 * falls back to the statistical model — but unlike the previous version, the
 * failure is logged rather than swallowed, so a dead integration cannot sit
 * unnoticed behind a silent fallback.
 */
class ClaudePredictionService
{
    public const DEFAULT_MODEL = 'claude-opus-5';
    public const DEFAULT_EFFORT = 'medium';

    public function __construct(protected ?Client $client = null) {}

    public function isConfigured(): bool
    {
        return $this->apiKey() !== '';
    }

    protected function apiKey(): string
    {
        $key = Setting::credential('claude_api_key', 'services.claude.key');

        // A placeholder key is not a configured key.
        return str_contains(strtoupper($key), 'MOCK') ? '' : $key;
    }

    protected function client(): Client
    {
        return $this->client ??= new Client(apiKey: $this->apiKey());
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

        // Only markets this fixture can support: asking for a corners
        // probability on a fixture with no corner rates would get a confident
        // answer invented from club reputation, and one that can never be
        // settled because no corner result will arrive for it either.
        $markets = MarketRegistry::generatedFor($match->hasCountStats());

        try {
            $message = $this->client()->messages->create(
                maxTokens: 8192,
                messages: [['role' => 'user', 'content' => $this->prompt($match, $markets)]],
                model: (string) Setting::get('claude_model', self::DEFAULT_MODEL),
                outputConfig: OutputConfig::with(
                    effort: (string) Setting::get('claude_effort', self::DEFAULT_EFFORT),
                    format: JSONOutputFormat::with(schema: $this->schema($markets)),
                ),
                system: 'You are a quantitative football analyst. Estimate genuine probabilities '
                    .'from the evidence supplied. Do not inflate confidence: if a market is close '
                    .'to a coin flip, say so. Probabilities are decimals between 0 and 1.',
            );
        } catch (APIStatusException $e) {
            Log::error('Claude prediction request failed', [
                'match_id' => $match->id,
                'status' => $e->getCode(),
                'message' => $e->getMessage(),
            ]);

            return null;
        } catch (AnthropicException $e) {
            Log::error('Claude prediction transport failure', [
                'match_id' => $match->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        if ($message->stopReason === 'refusal') {
            Log::warning('Claude declined the prediction request', ['match_id' => $match->id]);

            return null;
        }

        return $this->normalise($this->extractPayload($message), $match, array_keys($markets));
    }

    /**
     * Pull the structured payload out of the response.
     *
     * @return array<string, mixed>
     */
    protected function extractPayload(object $message): array
    {
        foreach ($message->content ?? [] as $block) {
            if (($block->type ?? null) !== 'text') {
                continue;
            }

            // Structured outputs populate `parsed`; fall back to decoding the
            // text if an older API version omits it.
            if (is_array($block->parsed ?? null)) {
                return $block->parsed;
            }

            $decoded = json_decode($block->text ?? '', true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * Validate and clamp the model's output.
     *
     * The schema constrains shape and pick vocabulary but cannot constrain
     * numeric ranges, so probabilities are range-checked here. A market that
     * fails validation is dropped rather than coerced into a plausible-looking
     * number.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $expected  Market keys this fixture supports.
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
                Log::warning('Claude returned an out-of-range probability', [
                    'match_id' => $match->id,
                    'market' => $market,
                    'probability' => $probability,
                ]);

                continue;
            }

            $markets[$market] = [
                'pick' => $pick,
                'probability' => $probability,
                'rationale' => trim((string) ($entry['rationale'] ?? '')) ?: 'Claude analysis.',
            ];
        }

        if ($markets === []) {
            Log::warning('Claude response contained no usable markets', ['match_id' => $match->id]);

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
            // win and win_draw_loss are two readings of one match, so a home
            // side cannot be 60% to win outright and 40% on the 1X2.
            'Keep the markets mutually consistent: they describe the same match.',
            'Keep each rationale under 200 characters.',
        ]);
    }

    /**
     * @param  array<string, \App\Support\Market>  $markets
     * @return array<string, mixed>
     */
    protected function schema(array $markets): array
    {
        $shape = fn (array $picks): array => [
            'type' => 'object',
            'properties' => [
                'pick' => ['type' => 'string', 'enum' => $picks],
                'probability' => ['type' => 'number'],
                'rationale' => ['type' => 'string'],
            ],
            'required' => ['pick', 'probability', 'rationale'],
            'additionalProperties' => false,
        ];

        $properties = [];

        foreach ($markets as $key => $market) {
            $properties[$key] = $shape($market->selections());
        }

        return [
            'type' => 'object',
            'properties' => $properties,
            'required' => array_keys($properties),
            'additionalProperties' => false,
        ];
    }
}
