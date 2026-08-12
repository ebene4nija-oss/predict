<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Expert;
use App\Models\ExpertPick;
use App\Models\GameMatch;
use App\Models\Prediction;
use App\Models\Result;
use App\Models\Subscription;
use App\Models\Setting;
use App\Services\PredictionService;
use App\Services\PreviewGenerationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed default AI settings & parameters
        Setting::set('gemini_api_key', env('GEMINI_API_KEY', ''));
        Setting::set('claude_api_key', env('CLAUDE_API_KEY', ''));
        Setting::set('prediction_provider', 'claude');
        Setting::set('min_confidence_threshold', '0.55');
        Setting::set('poisson_home_weight', '1.3');
        Setting::set('poisson_away_weight', '1.3');

        // 1. Create Users
        $admin = User::create([
            'name' => 'Platform Admin',
            'email' => 'admin@prophet.ai',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $expertUser = User::create([
            'name' => 'Dr. Marcus Vance',
            'email' => 'expert@prophet.ai',
            'password' => Hash::make('password'),
            'role' => 'expert',
        ]);

        $subscriberUser = User::create([
            'name' => 'Pro Subscriber',
            'email' => 'subscriber@prophet.ai',
            'password' => Hash::make('password'),
            'role' => 'subscriber',
        ]);

        $freeUser = User::create([
            'name' => 'Free User',
            'email' => 'free@prophet.ai',
            'password' => Hash::make('password'),
            'role' => 'free',
        ]);

        // Subscription for Pro Subscriber
        Subscription::create([
            'user_id' => $subscriberUser->id,
            'gateway' => 'flutterwave',
            'gateway_subscription_id' => 'flw_sub_seed_123',
            'status' => 'active',
            'plan' => 'monthly_pro',
            'renews_at' => now()->addMonth(),
        ]);

        // 2. Create Expert Profile
        $expert = Expert::create([
            'user_id' => $expertUser->id,
            'name' => 'Dr. Marcus Vance',
            'bio' => 'Former lead tactical analyst for UEFA analytics panel. Specializes in Premier League & Champions League expected goal modeling.',
            'photo_path' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=250',
        ]);

        $expert2 = Expert::create([
            'user_id' => null,
            'name' => 'Elena Rostova',
            'bio' => 'European Match Data Strategist focusing on Bundesliga and La Liga high-value goal markets.',
            'photo_path' => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&q=80&w=250',
        ]);

        // 3. Create Upcoming Fixtures & Predictions
        $predictionService = new PredictionService();
        $previewService = new PreviewGenerationService();

        $fixtures = [
            [
                'home_team' => 'Arsenal',
                'away_team' => 'Chelsea',
                'league' => 'Premier League',
                'kickoff_at' => Carbon::now()->addHours(6),
                'home_form' => ['gf' => 2.3, 'ga' => 0.7],
                'away_form' => ['gf' => 1.4, 'ga' => 1.5],
                'h2h_summary' => 'Arsenal won 3 of last 4 encounters at Emirates Stadium.',
                'injury_notes' => 'Saka fit to start; Reece James sidelined for Chelsea.',
            ],
            [
                'home_team' => 'Real Madrid',
                'away_team' => 'Barcelona',
                'league' => 'La Liga',
                'kickoff_at' => Carbon::now()->addHours(12),
                'home_form' => ['gf' => 2.6, 'ga' => 0.8],
                'away_form' => ['gf' => 2.4, 'ga' => 1.2],
                'h2h_summary' => 'Over 2.5 goals in 8 of last 10 El Clasico clashes.',
                'injury_notes' => 'Bellingham available; Gavi long-term absentee.',
            ],
            [
                'home_team' => 'Bayern Munich',
                'away_team' => 'Paris Saint-Germain',
                'league' => 'UEFA Champions League',
                'kickoff_at' => Carbon::now()->addHours(18),
                'home_form' => ['gf' => 2.8, 'ga' => 1.0],
                'away_form' => ['gf' => 2.1, 'ga' => 1.1],
                'h2h_summary' => 'Bayern unbeaten in last 4 European home matches.',
                'injury_notes' => 'Harry Kane leads attack; Dembele starting for PSG.',
            ],
            [
                'home_team' => 'Liverpool',
                'away_team' => 'Manchester City',
                'league' => 'Premier League',
                'kickoff_at' => Carbon::now()->addDays(1)->addHours(3),
                'home_form' => ['gf' => 2.4, 'ga' => 0.9],
                'away_form' => ['gf' => 2.5, 'ga' => 1.0],
                'h2h_summary' => 'Last 5 games at Anfield averaged 3.4 total goals.',
                'injury_notes' => 'Mohamed Salah starts; De Bruyne doubtful.',
            ],
            [
                'home_team' => 'Inter Milan',
                'away_team' => 'Juventus',
                'league' => 'Serie A',
                'kickoff_at' => Carbon::now()->addDays(1)->addHours(9),
                'home_form' => ['gf' => 1.9, 'ga' => 0.5],
                'away_form' => ['gf' => 1.3, 'ga' => 0.6],
                'h2h_summary' => 'Tactical defensive battles: Under 2.5 in 7 of last 9.',
                'injury_notes' => 'Lautaro Martinez fit; Vlahovic starting.',
            ],
            [
                'home_team' => 'Borussia Dortmund',
                'away_team' => 'Bayer Leverkusen',
                'league' => 'Bundesliga',
                'kickoff_at' => Carbon::now()->addDays(2),
                'home_form' => ['gf' => 2.1, 'ga' => 1.4],
                'away_form' => ['gf' => 2.5, 'ga' => 1.0],
                'h2h_summary' => 'Both teams scored in 9 of their last 10 meetings.',
                'injury_notes' => 'Brandt doubtful; Florian Wirtz starting.',
            ],
            [
                'home_team' => 'Atletico Madrid',
                'away_team' => 'Sevilla',
                'league' => 'La Liga',
                'kickoff_at' => Carbon::now()->addDays(2)->addHours(4),
                'home_form' => ['gf' => 1.8, 'ga' => 0.7],
                'away_form' => ['gf' => 1.1, 'ga' => 1.3],
                'h2h_summary' => 'Atletico Madrid won 4 of last 5 at Metropolitano.',
                'injury_notes' => 'Griezmann fit; En-Nesyri starting.',
            ],
            [
                'home_team' => 'Tottenham Hotspur',
                'away_team' => 'Aston Villa',
                'league' => 'Premier League',
                'kickoff_at' => Carbon::now()->addDays(2)->addHours(8),
                'home_form' => ['gf' => 2.1, 'ga' => 1.6],
                'away_form' => ['gf' => 1.9, 'ga' => 1.4],
                'h2h_summary' => 'High pressing clashes averaging 3.8 goals per match.',
                'injury_notes' => 'Son Heung-min starting; Ollie Watkins in form.',
            ],
        ];

        foreach ($fixtures as $index => $data) {
            $match = GameMatch::create($data);

            // Compute AI Predictions
            $predictions = $predictionService->calculateAndStore($match);

            // Generate narrative preview
            $previewService->generatePreview($match);

            // Create Expert Pick for key matches
            if ($index % 2 === 0) {
                ExpertPick::create([
                    'expert_id' => $expert->id,
                    'match_id' => $match->id,
                    'market' => 'win_draw_loss',
                    'pick' => $match->home_team . ' Win',
                    'rationale' => "{$match->home_team}'s high pressing intensity will overpower {$match->away_team} in transition.",
                    'confidence' => 0.8200,
                ]);

                ExpertPick::create([
                    'expert_id' => $expert2->id,
                    'match_id' => $match->id,
                    'market' => 'over_2_5',
                    'pick' => 'Over 2.5',
                    'rationale' => "Offensive expected goals indicate at least 3 total match goals.",
                    'confidence' => 0.7800,
                ]);
            }
        }

        // Rank Top 10 and AI 5
        (new \App\Jobs\RankingJob())->handle();
        (new \App\Jobs\Ai5SelectionJob())->handle();

        // 4. Create Historical Matches & Settled Results for Track Record
        $pastFixtures = [
            ['home' => 'Manchester City', 'away' => 'Manchester United', 'league' => 'Premier League', 'hScore' => 3, 'aScore' => 1],
            ['home' => 'Barcelona', 'away' => 'Girona', 'league' => 'La Liga', 'hScore' => 2, 'aScore' => 1],
            ['home' => 'PSG', 'away' => 'Marseille', 'league' => 'Ligue 1', 'hScore' => 4, 'aScore' => 0],
            ['home' => 'Bayer Leverkusen', 'away' => 'RB Leipzig', 'league' => 'Bundesliga', 'hScore' => 2, 'aScore' => 2],
            ['home' => 'AC Milan', 'away' => 'Napoli', 'league' => 'Serie A', 'hScore' => 1, 'aScore' => 0],
        ];

        foreach ($pastFixtures as $p) {
            $pastMatch = GameMatch::create([
                'home_team' => $p['home'],
                'away_team' => $p['away'],
                'league' => $p['league'],
                'kickoff_at' => Carbon::now()->subDays(3),
                'preview_text' => "Historical match analysis.",
            ]);

            Prediction::create([
                'match_id' => $pastMatch->id,
                'market' => 'win_draw_loss',
                'pick' => $p['hScore'] > $p['aScore'] ? 'Home Win' : ($p['hScore'] === $p['aScore'] ? 'Draw' : 'Away Win'),
                'probability' => 0.7800,
                'is_top10' => false,
                'is_ai5' => false,
            ]);

            Prediction::create([
                'match_id' => $pastMatch->id,
                'market' => 'over_2_5',
                'pick' => ($p['hScore'] + $p['aScore']) > 2.5 ? 'Over 2.5' : 'Under 2.5',
                'probability' => 0.7500,
            ]);

            ExpertPick::create([
                'expert_id' => $expert->id,
                'match_id' => $pastMatch->id,
                'market' => 'win_draw_loss',
                'pick' => $p['hScore'] > $p['aScore'] ? 'Home Win' : ($p['hScore'] === $p['aScore'] ? 'Draw' : 'Away Win'),
                'rationale' => "Correct tactical read on dominant home performance.",
                'confidence' => 0.8000,
            ]);

            Result::create([
                'match_id' => $pastMatch->id,
                'home_score' => $p['hScore'],
                'away_score' => $p['aScore'],
                'actual_outcome' => [
                    'wdl' => $p['hScore'] > $p['aScore'] ? 'Home Win' : ($p['hScore'] === $p['aScore'] ? 'Draw' : 'Away Win'),
                    'gg' => ($p['hScore'] > 0 && $p['aScore'] > 0) ? 'GG (Yes)' : 'NG (No)',
                    'over_2_5' => ($p['hScore'] + $p['aScore']) > 2.5 ? 'Over 2.5' : 'Under 2.5',
                ],
                'settled_at' => Carbon::now()->subDays(2),
            ]);
        }
    }
}
