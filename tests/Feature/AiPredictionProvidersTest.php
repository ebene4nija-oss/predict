<?php

namespace Tests\Feature;

use App\Models\GameMatch;
use App\Models\Prediction;
use App\Models\Setting;
use App\Models\User;
use App\Services\GeminiPredictionService;
use App\Services\KimiPredictionService;
use App\Services\OpenAiPredictionService;
use App\Services\PredictionService;
use App\Support\IntegrationTester;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiPredictionProvidersTest extends TestCase
{
    use RefreshDatabase;

    protected function makeMatch(): GameMatch
    {
        return GameMatch::create([
            'home_team' => 'Arsenal',
            'away_team' => 'Chelsea',
            'league' => 'Premier League',
            'kickoff_at' => now()->addHours(6),
            'home_form' => ['gf' => 2.2, 'ga' => 0.7],
            'away_form' => ['gf' => 1.3, 'ga' => 1.5],
        ]);
    }

    protected function sampleAiPayload(): array
    {
        return [
            'win_draw_loss' => ['pick' => 'Home Win', 'probability' => 0.68, 'rationale' => 'Arsenal strong home form.'],
            'over_2_5' => ['pick' => 'Over 2.5 Goals', 'probability' => 0.62, 'rationale' => 'High expected goals.'],
            'gg' => ['pick' => 'Yes (Both Teams to Score)', 'probability' => 0.58, 'rationale' => 'Both teams scoring regularly.'],
            'ht_ft' => ['pick' => 'Home/Home', 'probability' => 0.51, 'rationale' => 'Arsenal leading at half-time.'],
            'double_chance' => ['pick' => 'Home Win or Draw (1X)', 'probability' => 0.88, 'rationale' => 'Very low loss risk.'],
            'draw_no_bet' => ['pick' => 'Arsenal (Home Draw No Bet)', 'probability' => 0.79, 'rationale' => 'Arsenal favored.'],
            'handicap' => ['pick' => 'Arsenal -1.5 (Home -1.5)', 'probability' => 0.44, 'rationale' => 'Possible 2-goal margin.'],
            'correct_score' => ['pick' => '2-0', 'probability' => 0.22, 'rationale' => 'Most likely scoreline.'],
            'win_to_nil' => ['pick' => 'Home Win to Nil', 'probability' => 0.41, 'rationale' => 'Solid Arsenal defence.'],
            'clean_sheet' => ['pick' => 'Home Clean Sheet', 'probability' => 0.52, 'rationale' => 'Clean sheet likely.'],
            'highest_scoring_half' => ['pick' => '2nd Half', 'probability' => 0.55, 'rationale' => 'Open second half.'],
            'total_goals_exact' => ['pick' => '2 Goals', 'probability' => 0.33, 'rationale' => 'Moderate scoring.'],
            'over_1_5' => ['pick' => 'Over 1.5 Goals', 'probability' => 0.83, 'rationale' => 'Very likely 2+ goals.'],
            'over_3_5' => ['pick' => 'Under 3.5 Goals', 'probability' => 0.72, 'rationale' => 'Cap at 3 goals.'],
            'btts_over_2_5' => ['pick' => 'Yes & Over 2.5', 'probability' => 0.54, 'rationale' => 'Open encounter.'],
            'ht_result' => ['pick' => 'Home Win (1)', 'probability' => 0.48, 'rationale' => 'Arsenal early pressure.'],
            'ht_over_0_5' => ['pick' => 'Over 0.5 (1+ 1st Half Goals)', 'probability' => 0.74, 'rationale' => 'First half goal.'],
            'ht_over_1_5' => ['pick' => 'Under 1.5 (0-1 1st Half Goals)', 'probability' => 0.65, 'rationale' => 'Tight start.'],
            'ht_btts' => ['pick' => 'No (Clean sheet for at least one team in 1H)', 'probability' => 0.79, 'rationale' => 'Low 1H BTTS.'],
            'ht_correct_score' => ['pick' => '1-0', 'probability' => 0.38, 'rationale' => '1-0 at the break.'],
        ];
    }

    public function test_openai_prediction_service_generates_picks(): void
    {
        Setting::set('openai_api_key', 'sk-test-openai-key');
        Setting::set('openai_model', 'gpt-4o');

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode($this->sampleAiPayload()),
                        ],
                    ],
                ],
            ]),
        ]);

        $match = $this->makeMatch();
        $service = app(OpenAiPredictionService::class);
        $result = $service->generate($match);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('win_draw_loss', $result);
        $this->assertEquals('Home Win', $result['win_draw_loss']['pick']);
        $this->assertEquals(0.68, $result['win_draw_loss']['probability']);
    }

    public function test_gemini_prediction_service_generates_picks(): void
    {
        Setting::set('gemini_api_key', 'AIzaSy-test-key');
        Setting::set('gemini_prediction_model', 'gemini-2.5-flash');

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => json_encode($this->sampleAiPayload())],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $match = $this->makeMatch();
        $service = app(GeminiPredictionService::class);
        $result = $service->generate($match);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('win_draw_loss', $result);
        $this->assertEquals('Home Win', $result['win_draw_loss']['pick']);
        $this->assertEquals(0.68, $result['win_draw_loss']['probability']);
    }

    public function test_kimi_prediction_service_generates_picks(): void
    {
        Setting::set('kimi_api_key', 'sk-test-kimi-key');
        Setting::set('kimi_model', 'moonshot-v1-8k');

        Http::fake([
            'https://api.moonshot.cn/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode($this->sampleAiPayload()),
                        ],
                    ],
                ],
            ]),
        ]);

        $match = $this->makeMatch();
        $service = app(KimiPredictionService::class);
        $result = $service->generate($match);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('win_draw_loss', $result);
        $this->assertEquals('Home Win', $result['win_draw_loss']['pick']);
        $this->assertEquals(0.68, $result['win_draw_loss']['probability']);
    }

    public function test_prediction_service_routes_to_configured_provider_and_stores_source(): void
    {
        $match = $this->makeMatch();

        // 1. Test OpenAI / ChatGPT provider
        Setting::set('prediction_provider', 'chatgpt');
        Setting::set('openai_api_key', 'sk-test-openai');
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => json_encode($this->sampleAiPayload())]]],
            ]),
        ]);

        $predictions = app(PredictionService::class)->calculateAndStore($match);
        $this->assertEquals('chatgpt', $predictions['win_draw_loss']->source);

        // 2. Test Gemini provider
        Setting::set('prediction_provider', 'gemini');
        Setting::set('gemini_api_key', 'AIzaSy-test');
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode($this->sampleAiPayload())]]]]],
            ]),
        ]);

        $predictions = app(PredictionService::class)->calculateAndStore($match);
        $this->assertEquals('gemini', $predictions['win_draw_loss']->source);

        // 3. Test Kimi provider
        Setting::set('prediction_provider', 'kimi');
        Setting::set('kimi_api_key', 'sk-test-kimi');
        Http::fake([
            'https://api.moonshot.cn/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => json_encode($this->sampleAiPayload())]]],
            ]),
        ]);

        $predictions = app(PredictionService::class)->calculateAndStore($match);
        $this->assertEquals('kimi', $predictions['win_draw_loss']->source);
    }

    public function test_ai_provider_gracefully_falls_back_to_poisson_when_api_fails(): void
    {
        $match = $this->makeMatch();

        Setting::set('prediction_provider', 'chatgpt');
        Setting::set('openai_api_key', 'sk-test-openai');
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response(['error' => 'quota exceeded'], 429),
        ]);

        $predictions = app(PredictionService::class)->calculateAndStore($match);

        $this->assertNotEmpty($predictions);
        $this->assertEquals('poisson_fallback', $predictions['win_draw_loss']->source);
    }

    public function test_integration_tester_checks_openai_and_kimi(): void
    {
        $tester = app(IntegrationTester::class);

        // Without keys
        Setting::set('openai_api_key', '');
        Setting::set('kimi_api_key', '');
        $this->assertFalse($tester->test('openai')['ok']);
        $this->assertFalse($tester->test('kimi')['ok']);

        // With working keys
        Setting::set('openai_api_key', 'sk-valid-key');
        Setting::set('kimi_api_key', 'sk-valid-key');

        Http::fake([
            'https://api.openai.com/v1/models' => Http::response(['data' => [['id' => 'gpt-4o']]]),
            'https://api.moonshot.cn/v1/models' => Http::response(['data' => [['id' => 'moonshot-v1-8k']]]),
        ]);

        $openaiRes = $tester->test('openai');
        $this->assertTrue($openaiRes['ok']);
        $this->assertStringContainsString('1 models available', $openaiRes['message']);

        $kimiRes = $tester->test('kimi');
        $this->assertTrue($kimiRes['ok']);
        $this->assertStringContainsString('1 models available', $kimiRes['message']);
    }
}
