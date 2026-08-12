<?php

namespace App\Jobs;

use App\Models\GameMatch;
use App\Services\PredictionService;
use App\Services\PreviewGenerationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Carbon\Carbon;

class FixtureIngestionJob implements ShouldQueue
{
    use Queueable;

    public function handle(PredictionService $predictionService, PreviewGenerationService $previewService): void
    {
        // Sample fixture data for Premier League, Champions League, La Liga
        $fixtures = [
            [
                'home_team' => 'Arsenal',
                'away_team' => 'Chelsea',
                'league' => 'Premier League',
                'kickoff_at' => Carbon::now()->addHours(6),
                'home_form' => ['gf' => 2.2, 'ga' => 0.8],
                'away_form' => ['gf' => 1.7, 'ga' => 1.2],
                'h2h_summary' => 'Arsenal won 3 of the last 5 encounters, with 2 draws.',
                'injury_notes' => 'Saka fit to start; Chelsea missing Recce James.',
            ],
            [
                'home_team' => 'Real Madrid',
                'away_team' => 'Barcelona',
                'league' => 'La Liga',
                'kickoff_at' => Carbon::now()->addHours(12),
                'home_form' => ['gf' => 2.5, 'ga' => 0.9],
                'away_form' => ['gf' => 2.4, 'ga' => 1.1],
                'h2h_summary' => '32 goals scored in last 8 El Clasico matches.',
                'injury_notes' => 'Bellingham available; Gavi long-term absentee.',
            ],
            [
                'home_team' => 'Bayern Munich',
                'away_team' => 'Paris Saint-Germain',
                'league' => 'UEFA Champions League',
                'kickoff_at' => Carbon::now()->addHours(18),
                'home_form' => ['gf' => 2.8, 'ga' => 1.1],
                'away_form' => ['gf' => 2.1, 'ga' => 1.0],
                'h2h_summary' => 'Bayern won 4 of last 6 Champions League ties.',
                'injury_notes' => 'Kane starts up front; Dembele returns from illness.',
            ],
            [
                'home_team' => 'Liverpool',
                'away_team' => 'Manchester City',
                'league' => 'Premier League',
                'kickoff_at' => Carbon::now()->addDays(1)->addHours(2),
                'home_form' => ['gf' => 2.3, 'ga' => 0.9],
                'away_form' => ['gf' => 2.6, 'ga' => 1.0],
                'h2h_summary' => 'Last 4 meetings at Anfield produced 14 goals.',
                'injury_notes' => 'Salah available; De Bruyne doubtful.',
            ],
            [
                'home_team' => 'Inter Milan',
                'away_team' => 'Juventus',
                'league' => 'Serie A',
                'kickoff_at' => Carbon::now()->addDays(1)->addHours(8),
                'home_form' => ['gf' => 1.9, 'ga' => 0.6],
                'away_form' => ['gf' => 1.4, 'ga' => 0.7],
                'h2h_summary' => 'Derby d\'Italia averages under 2.5 goals in 7 of last 10.',
                'injury_notes' => 'Lautaro Martinez leads the line; Vlahovic starting.',
            ],
            [
                'home_team' => 'Borussia Dortmund',
                'away_team' => 'Bayer Leverkusen',
                'league' => 'Bundesliga',
                'kickoff_at' => Carbon::now()->addDays(2),
                'home_form' => ['gf' => 2.0, 'ga' => 1.4],
                'away_form' => ['gf' => 2.4, 'ga' => 1.1],
                'h2h_summary' => 'Both teams scored in 9 of last 10 meetings.',
                'injury_notes' => 'Brandt doubtful; Wirtz starting.',
            ],
        ];

        foreach ($fixtures as $data) {
            $match = GameMatch::updateOrCreate(
                ['home_team' => $data['home_team'], 'away_team' => $data['away_team'], 'league' => $data['league']],
                $data
            );

            // Compute Poisson predictions
            $predictionService->calculateAndStore($match);

            // Generate narrative preview via Gemini / fallback
            $previewService->generatePreview($match);
        }
    }
}
