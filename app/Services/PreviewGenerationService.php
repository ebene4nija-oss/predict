<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PreviewGenerationService
{
    /**
     * Used when neither the dashboard nor the environment names a model.
     */
    public const DEFAULT_MODEL = 'gemini-2.5-flash';

    /**
     * Check if Gemini API is properly configured.
     */
    public function isConfigured(): bool
    {
        $apiKey = Setting::credential('gemini_api_key', 'services.gemini.key');

        return !empty($apiKey) && !str_contains(strtoupper($apiKey), 'MOCK');
    }

    /**
     * Generate comprehensive, search-engine-optimized narrative match preview.
     */
    public function generatePreview(GameMatch $match, ?string $customInstruction = null, bool $isAdminManual = false): string
    {
        $match->load('predictions');

        $predictionsSummary = $match->predictions->map(function ($p) {
            return "{$p->market}: Pick {$p->pick} (" . round($p->probability * 100, 1) . "% confidence - {$p->rationale})";
        })->implode('; ');

        $homeGf = is_array($match->home_form) ? ($match->home_form['gf'] ?? 1.8) : 1.8;
        $homeGa = is_array($match->home_form) ? ($match->home_form['ga'] ?? 1.0) : 1.0;
        $awayGf = is_array($match->away_form) ? ($match->away_form['gf'] ?? 1.2) : 1.2;
        $awayGa = is_array($match->away_form) ? ($match->away_form['ga'] ?? 1.5) : 1.5;

        $apiKey = Setting::credential('gemini_api_key', 'services.gemini.key');

        if ($this->isConfigured()) {
            try {
                $prompt = "You are an elite football tactical analyst and SEO sports editor. Write a comprehensive, search-engine-optimized (SEO) match preview for '{$match->home_team} vs {$match->away_team}' in the {$match->league}.\n\n"
                    . "Match Details:\n"
                    . "- Competition: {$match->league}\n"
                    . "- Kickoff: " . ($match->kickoff_at ? $match->kickoff_at->format('l, F d, Y @ H:i') . ' UTC' : 'Upcoming') . "\n"
                    . "- Home Team: {$match->home_team} (Attack rating: {$homeGf} GF/game, Defense: {$homeGa} GA/game)\n"
                    . "- Away Team: {$match->away_team} (Attack rating: {$awayGf} GF/game, Defense: {$awayGa} GA/game)\n"
                    . "- Head-to-Head History: " . ($match->h2h_summary ?: 'Balanced recent record between both clubs.') . "\n"
                    . "- Squad News & Absences: " . ($match->injury_notes ?: 'No major squad absences reported. Both managers have standard selection options.') . "\n"
                    . "- AI Poisson Probability Calculations: " . ($predictionsSummary ?: 'Poisson xG matrix active') . "\n\n";

                if (filled($customInstruction)) {
                    $prompt .= "Editor's Specific Focus Instructions: {$customInstruction}\n\n";
                }

                $prompt .= "Content Structure Requirements (Write in engaging, authoritative Markdown format with clear H2 headings):\n"
                    . "1. ## Match Overview & Tactical Context (Set the stakes, form trajectories, and match narrative)\n"
                    . "2. ## Tactical Analysis & Key Matchups (Break down playing styles, pressing structures, midfield control, transition threat)\n"
                    . "3. ## Head-to-Head & Statistical Form Guide (Recent meetings trends, goal averages, home vs away dynamics)\n"
                    . "4. ## Team News & Squad Dynamics (Tactical impact of missing or key returning players)\n"
                    . "5. ## Expected Goals (xG) & Mathematical Probability Outlook (Poisson model breakdown, over/under tempo, goal expectations)\n"
                    . "6. ## Score Prediction & Key Takeaways (Projected match outcome, value betting angles, key summary bullets)\n\n"
                    . "SEO & Formatting Guidelines:\n"
                    . "- Naturally integrate high-intent search keywords: '{$match->home_team} vs {$match->away_team} prediction', '{$match->league} betting analysis', 'expected goals xG preview', 'football match preview', '{$match->home_team} lineup news'.\n"
                    . "- Use clean Markdown formatting with standard headings, bold bullet points, and markdown tables for stats.\n"
                    . "- DO NOT output raw ASCII pitch diagrams, spaced bracket formations (e.g. '[ Oblak ]'), or ASCII progress bars (e.g. '[█████]'). Write fluent, well-structured analytical prose and bullet points.\n"
                    . "- Do not output raw HTML tags.\n"
                    . "- Maintain an objective, data-backed, analytical tone tailored for sports enthusiasts and bettors.";

                $response = Http::timeout(12)->post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model()}:generateContent?key={$apiKey}", [
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
                        $cleaned = trim($text);
                        $this->store($match, $cleaned, GameMatch::PREVIEW_SOURCE_MODEL, $isAdminManual);

                        return $cleaned;
                    }
                }
            } catch (\Throwable $e) {
                // Fallback to structured tactical preview
            }
        }

        // Fallback comprehensive structured tactical preview
        $preview = $this->buildComprehensiveFallback($match, $homeGf, $homeGa, $awayGf, $awayGa);
        $this->store($match, $preview, GameMatch::PREVIEW_SOURCE_FALLBACK, $isAdminManual);

        return $preview;
    }

    /**
     * Build rich, structured fallback preview when AI is offline or key is unconfigured.
     */
    public function buildComprehensiveFallback(GameMatch $match, float $homeGf, float $homeGa, float $awayGf, float $awayGa): string
    {
        $h2h = $match->h2h_summary ?: 'Recent historical encounters between these sides demonstrate a closely contested dynamic with tactical adjustments playing a pivotal role.';
        $injuries = $match->injury_notes ?: 'Both managers appear to have relatively healthy rosters with no critical last-minute suspensions confirmed.';

        return "## Match Overview & Tactical Context\n"
            . "{$match->home_team} prepare to host {$match->away_team} in a highly anticipated {$match->league} fixture. "
            . "The hosts enter this clash aiming to leverage home advantage and maintain tactical discipline, while {$match->away_team} will seek to exploit transitional spaces and dictate tempo through midfield possession.\n\n"
            . "## Tactical Breakdown & Key Matchups\n"
            . "Tactically, {$match->home_team} average {$homeGf} goals scored per match while conceding {$homeGa}, highlighting their balanced attacking output against structured defensive blocks. "
            . "Conversely, {$match->away_team} average {$awayGf} goals scored and {$awayGa} goals conceded per fixture, indicating potential vulnerability against swift counter-pressing passages.\n\n"
            . "## Head-to-Head & Statistical Form Guide\n"
            . "{$h2h}\n\n"
            . "## Team News & Absences\n"
            . "{$injuries}\n\n"
            . "## Expected Goals (xG) & Mathematical Outlook\n"
            . "Poisson expected-goals modeling suggests a competitive fixture with offensive efficiency being the decisive factor in determining the outcome.\n\n"
            . "## Score Prediction & Key Takeaways\n"
            . "- **Home Advantage**: {$match->home_team} hold tactical leverage in early pressing sequences.\n"
            . "- **Key Metric**: Expected goals (xG) metrics indicate value in tightly contested margin markets.\n"
            . "- **Projected Flow**: A measured tactical opening giving way to dynamic end-to-end phases.";
    }

    /**
     * The Gemini model previews are written with.
     */
    public function model(): string
    {
        return Setting::credential('gemini_model', 'services.gemini.model') ?: self::DEFAULT_MODEL;
    }

    /**
     * Persist a preview and stamp how it was produced.
     */
    protected function store(GameMatch $match, string $text, string $source, bool $isAdminManual = false): void
    {
        $attributes = [
            'preview_text' => $text,
            'preview_source' => $source,
            'preview_generated_at' => $match->preview_generated_at ?? now(),
        ];

        // Generate smart default SEO tags if empty
        if (blank($match->seo_title)) {
            $attributes['seo_title'] = "{$match->home_team} vs {$match->away_team} Prediction & Tactical AI Preview — {$match->league}";
        }

        if (blank($match->seo_description)) {
            $date = $match->kickoff_at ? $match->kickoff_at->format('M d, Y H:i') : 'upcoming';
            $attributes['seo_description'] = Str::limit("Comprehensive {$match->home_team} vs {$match->away_team} prediction, Poisson expected goals (xG) analysis, tactical breakdown, and betting odds tips for {$match->league} kickoff {$date}.", 250);
        }

        if (blank($match->seo_keywords)) {
            $attributes['seo_keywords'] = strtolower("{$match->home_team} vs {$match->away_team} prediction, {$match->home_team} vs {$match->away_team} betting tips, {$match->league} AI preview, expected goals xG breakdown, football tactical analysis");
        }

        if (blank($match->preview_headline)) {
            $attributes['preview_headline'] = "{$match->home_team} vs {$match->away_team} Prediction, Tactical Breakdown & AI Analysis";
        }

        if ($isAdminManual) {
            $attributes['is_preview_custom'] = true;
            $attributes['preview_refreshed_at'] = now();
        } elseif ($source === GameMatch::PREVIEW_SOURCE_MODEL && $match->isInPreviewRefreshWindow()) {
            $attributes['preview_refreshed_at'] = now();
        }

        $match->update($attributes);
    }
}
