<?php

namespace Tests\Feature;

use App\Models\GameMatch;
use App\Models\Result;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The doc asks for the model to be validated against real history before it
 * goes live, not just reasoned about on paper. These cover the harness that
 * does it.
 */
class BacktestCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function fixture(array $home, array $away, int $homeScore, int $awayScore, string $league = 'Premier League'): void
    {
        $match = GameMatch::create([
            'home_team' => 'Home '.uniqid(),
            'away_team' => 'Away '.uniqid(),
            'league' => $league,
            'kickoff_at' => now()->subDays(7),
            'home_form' => $home,
            'away_form' => $away,
        ]);

        Result::create([
            'match_id' => $match->id,
            'home_score' => $homeScore,
            'away_score' => $awayScore,
            'settled_at' => now()->subDays(6),
        ]);
    }

    public function test_it_refuses_to_report_with_no_history(): void
    {
        $this->artisan('model:backtest')
            ->expectsOutputToContain('No fixtures with both a result and usable form data')
            ->assertExitCode(1);
    }

    public function test_it_scores_fixtures_that_have_form_and_a_result(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->fixture(['gf' => 2.1, 'ga' => 0.8], ['gf' => 0.9, 'ga' => 1.9], 2, 0);
        }

        $this->artisan('model:backtest')
            ->expectsOutputToContain('Backtested 10 fixture(s)')
            ->expectsOutputToContain('WIN DRAW LOSS')
            ->assertExitCode(0);
    }

    public function test_it_skips_fixtures_with_no_form_rather_than_scoring_a_coin_flip(): void
    {
        $this->fixture(['gf' => 2.1, 'ga' => 0.8], ['gf' => 0.9, 'ga' => 1.9], 2, 0);

        // No form on either side: the engine would fall back to league average
        // for both teams, which is not a prediction worth grading.
        $bare = GameMatch::create([
            'home_team' => 'Bare Home',
            'away_team' => 'Bare Away',
            'league' => 'Premier League',
            'kickoff_at' => now()->subDays(7),
        ]);
        Result::create([
            'match_id' => $bare->id,
            'home_score' => 1,
            'away_score' => 1,
            'settled_at' => now()->subDays(6),
        ]);

        $this->artisan('model:backtest')
            ->expectsOutputToContain('Backtested 1 fixture(s)')
            ->expectsOutputToContain('Skipped 1 without usable form')
            ->assertExitCode(0);
    }

    public function test_it_warns_when_the_sample_is_too_small_to_mean_anything(): void
    {
        $this->fixture(['gf' => 2.1, 'ga' => 0.8], ['gf' => 0.9, 'ga' => 1.9], 2, 0);

        $this->artisan('model:backtest --min=30')
            ->expectsOutputToContain('noise, not evidence')
            ->assertExitCode(0);
    }

    public function test_it_flags_a_model_that_is_more_confident_than_it_is_right(): void
    {
        // Strong home sides that all lost: the engine tips the home win with
        // high confidence every time and is wrong every time.
        for ($i = 0; $i < 12; $i++) {
            $this->fixture(['gf' => 3.0, 'ga' => 0.4], ['gf' => 0.5, 'ga' => 2.6], 0, 3);
        }

        $this->artisan('model:backtest --min=1')
            ->expectsOutputToContain('Over-confident by')
            ->assertExitCode(0);
    }

    public function test_the_date_range_filters_what_is_scored(): void
    {
        $this->fixture(['gf' => 2.1, 'ga' => 0.8], ['gf' => 0.9, 'ga' => 1.9], 2, 0);

        $this->artisan('model:backtest --from='.now()->addDay()->toDateString())
            ->assertExitCode(1);
    }

    public function test_it_can_be_restricted_to_one_competition(): void
    {
        $this->fixture(['gf' => 2.1, 'ga' => 0.8], ['gf' => 0.9, 'ga' => 1.9], 2, 0, 'Premier League');
        $this->fixture(['gf' => 2.1, 'ga' => 0.8], ['gf' => 0.9, 'ga' => 1.9], 2, 0, 'Serie A');

        $this->artisan('model:backtest --league="Serie A"')
            ->expectsOutputToContain('Backtested 1 fixture(s)')
            ->assertExitCode(0);
    }
}
