<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class PreviewGenerationService
{
    /**
     * Generate narrative match preview using Gemini API.
     * Match Previews are handled by Gemini AI.
     */
    public function generatePreview(GameMatch $match): string
    {
        $match->load('predictions');

        $predictionsSummary = $match->predictions->map(function ($p) {
            return "{$p->market}: Pick {$p->pick} (" . round($p->probability * 100, 1) . "% confidence)";
        })->implode('; ');

        $apiKey = Setting::get('gemini_api_key') ?? config('services.gemini.key') ?? env('GEMINI_API_KEY');

        if ($apiKey && !str_contains($apiKey, 'MOCK')) {
            try {
                $prompt = "Write a comprehensive, search-engine-optimized (SEO) tactical football match preview for '{$match->home_team} vs {$match->away_team}' in the {$match->league}. "
                    . "Include naturally integrated keywords such as '{$match->home_team} vs {$match->away_team} prediction', '{$match->league} betting analysis', 'expected goals xG breakdown', and 'tactical preview'. "
                    . "Match details: Home Form: " . json_encode($match->home_form) . ", Away Form: " . json_encode($match->away_form) . ". "
                    . "Head to head: {$match->h2h_summary}. Absences: {$match->injury_notes}. "
                    . "AI probabilities: {$predictionsSummary}. "
                    . "Format the response into 2 structured, data-dense, objective paragraphs without HTML tags. Make it engaging for football fans and search engines.";

                $response = Http::timeout(10)->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ]);

                if ($response->successful()) {
                    $json = $response->json();
                    $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    if ($text) {
                        $match->update(['preview_text' => trim($text)]);
                        return trim($text);
                    }
                }
            } catch (\Throwable $e) {
                // Fallback to structured preview
            }
        }

        // Fallback structured tactical preview
        $preview = "{$match->home_team} prepare to host {$match->away_team} in an intriguing {$match->league} clash. "
            . "{$match->home_team} enter this fixture looking to build momentum based on recent defensive performances. "
            . "{$match->away_team} will test the hosts with their sharp transition play and offensive output.\n\n"
            . "Statistical evaluation and expected-goals modeling suggest a competitive encounter. "
            . "Key factors include {$match->h2h_summary} alongside recent Squad updates: {$match->injury_notes}.";

        $match->update(['preview_text' => $preview]);
        return $preview;
    }
}
