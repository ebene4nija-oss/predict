<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\Prediction;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class ClaudePredictionService
{
    /**
     * Generate match predictions using Claude API or Claude-tuned engine
     * according to Admin-configured parameters.
     */
    public function generatePredictions(GameMatch $match): array
    {
        $apiKey = Setting::get('claude_api_key') ?? config('services.claude.key') ?? env('CLAUDE_API_KEY');
        $homeWeight = (float) Setting::get('poisson_home_weight', 1.3);
        $awayWeight = (float) Setting::get('poisson_away_weight', 1.3);
        $minConfidence = (float) Setting::get('min_confidence_threshold', 0.55);

        if ($apiKey && !str_contains($apiKey, 'MOCK')) {
            try {
                $prompt = "Analyze this football match and provide exact probability calculations for 3 markets: win_draw_loss, gg (both teams score), and over_2_5. "
                    . "Match: {$match->home_team} vs {$match->away_team} in {$match->league}. "
                    . "Home Form: " . json_encode($match->home_form) . ", Away Form: " . json_encode($match->away_form) . ". "
                    . "H2H: {$match->h2h_summary}. Absences: {$match->injury_notes}. "
                    . "Respond ONLY with a JSON object containing keys: win_draw_loss (pick, probability, rationale), gg (pick, probability, rationale), over_2_5 (pick, probability, rationale).";

                $response = Http::withHeaders([
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])->timeout(12)->post('https://api.anthropic.com/v1/messages', [
                    'model' => 'claude-3-5-sonnet-20241022',
                    'max_tokens' => 800,
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt]
                    ]
                ]);

                if ($response->successful()) {
                    $content = $response->json()['content'][0]['text'] ?? '';
                    $jsonStart = strpos($content, '{');
                    $jsonEnd = strrpos($content, '}');
                    if ($jsonStart !== false && $jsonEnd !== false) {
                        $jsonStr = substr($content, $jsonStart, $jsonEnd - $jsonStart + 1);
                        $parsed = json_decode($jsonStr, true);
                        if ($parsed && isset($parsed['win_draw_loss'])) {
                            return $this->storePredictionsFromAi($match, $parsed);
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Fallback to parameterized Poisson calculation
            }
        }

        // Default: Parameterized Poisson calculation engine using Admin-configured weights
        return $this->calculatePoissonWithAdminWeights($match, $homeWeight, $awayWeight, $minConfidence);
    }

    protected function storePredictionsFromAi(GameMatch $match, array $data): array
    {
        $predictions = [];
        foreach (['win_draw_loss', 'gg', 'over_2_5'] as $market) {
            if (isset($data[$market])) {
                $predictions[$market] = Prediction::updateOrCreate(
                    ['match_id' => $match->id, 'market' => $market],
                    [
                        'pick' => $data[$market]['pick'] ?? 'Home Win',
                        'probability' => min(0.98, max(0.50, (float) ($data[$market]['probability'] ?? 0.65))),
                        'rationale' => $data[$market]['rationale'] ?? 'Claude AI prediction engine output.',
                    ]
                );
            }
        }
        return $predictions;
    }

    protected function calculatePoissonWithAdminWeights(GameMatch $match, float $homeWeight, float $awayWeight, float $minConfidence): array
    {
        $homeForm = $match->home_form ?? ['gf' => 1.8, 'ga' => 1.0];
        $awayForm = $match->away_form ?? ['gf' => 1.2, 'ga' => 1.5];

        $lambdaHome = max(0.4, ($homeForm['gf'] ?? 1.5) * (($awayForm['ga'] ?? 1.2) / $homeWeight));
        $lambdaAway = max(0.3, ($awayForm['gf'] ?? 1.1) * (($homeForm['ga'] ?? 1.0) / $awayWeight));

        $maxGoals = 6;
        $probHome = [];
        $probAway = [];
        for ($i = 0; $i <= $maxGoals; $i++) {
            $probHome[$i] = (pow($lambdaHome, $i) * exp(-$lambdaHome)) / $this->factorial($i);
            $probAway[$i] = (pow($lambdaAway, $i) * exp(-$lambdaAway)) / $this->factorial($i);
        }

        $homeWinProb = 0; $drawProb = 0; $awayWinProb = 0;
        for ($h = 0; $h <= $maxGoals; $h++) {
            for ($a = 0; $a <= $maxGoals; $a++) {
                $p = $probHome[$h] * $probAway[$a];
                if ($h > $a) $homeWinProb += $p;
                elseif ($h === $a) $drawProb += $p;
                else $awayWinProb += $p;
            }
        }

        $wdlPick = 'Home Win'; $wdlProb = $homeWinProb;
        if ($drawProb > $homeWinProb && $drawProb > $awayWinProb) {
            $wdlPick = 'Draw'; $wdlProb = $drawProb;
        } elseif ($awayWinProb > $homeWinProb && $awayWinProb > $drawProb) {
            $wdlPick = 'Away Win'; $wdlProb = $awayWinProb;
        }

        $ggProb = (1.0 - $probHome[0]) * (1.0 - $probAway[0]);
        $ggPick = $ggProb >= 0.52 ? 'GG (Yes)' : 'NG (No)';
        $ggFinalProb = $ggPick === 'GG (Yes)' ? $ggProb : (1.0 - $ggProb);

        $over25Prob = 0;
        for ($h = 0; $h <= $maxGoals; $h++) {
            for ($a = 0; $a <= $maxGoals; $a++) {
                if (($h + $a) > 2.5) $over25Prob += ($probHome[$h] * $probAway[$a]);
            }
        }
        $overPick = $over25Prob >= 0.50 ? 'Over 2.5' : 'Under 2.5';
        $overFinalProb = $overPick === 'Over 2.5' ? $over25Prob : (1.0 - $over25Prob);

        $predictions = [];
        $predictions['win_draw_loss'] = Prediction::updateOrCreate(
            ['match_id' => $match->id, 'market' => 'win_draw_loss'],
            [
                'pick' => $wdlPick,
                'probability' => round(max($minConfidence, $wdlProb), 4),
                'rationale' => "Claude-tuned engine: expected goals {$match->home_team} " . round($lambdaHome, 2) . " - " . round($lambdaAway, 2) . " {$match->away_team}.",
            ]
        );

        $predictions['gg'] = Prediction::updateOrCreate(
            ['match_id' => $match->id, 'market' => 'gg'],
            [
                'pick' => $ggPick,
                'probability' => round(max($minConfidence, $ggFinalProb), 4),
                'rationale' => "Claude-tuned engine: both teams scoring probability estimated at " . round($ggProb * 100, 1) . "%.",
            ]
        );

        $predictions['over_2_5'] = Prediction::updateOrCreate(
            ['match_id' => $match->id, 'market' => 'over_2_5'],
            [
                'pick' => $overPick,
                'probability' => round(max($minConfidence, $overFinalProb), 4),
                'rationale' => "Claude-tuned engine: expected total goals " . round($lambdaHome + $lambdaAway, 2) . ".",
            ]
        );

        return $predictions;
    }

    private function factorial(int $n): float
    {
        if ($n <= 1) return 1.0;
        $res = 1.0;
        for ($i = 2; $i <= $n; $i++) $res *= $i;
        return $res;
    }
}
