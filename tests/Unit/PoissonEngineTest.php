<?php

namespace Tests\Unit;

use App\Support\PoissonEngine;
use PHPUnit\Framework\TestCase;

class PoissonEngineTest extends TestCase
{
    public function test_score_matrix_is_a_probability_distribution(): void
    {
        $matrix = PoissonEngine::scoreMatrix(1.8, 1.1);

        $total = 0.0;
        foreach ($matrix as $row) {
            foreach ($row as $probability) {
                $this->assertGreaterThanOrEqual(0.0, $probability);
                $total += $probability;
            }
        }

        $this->assertEqualsWithDelta(1.0, $total, 1e-9, 'score probabilities must sum to 1');
    }

    public function test_first_half_probabilities_are_coherent_and_quieter_than_the_match(): void
    {
        $lambdaHome = 1.6;
        $lambdaAway = 1.2;

        $half = PoissonEngine::halfTimeProbabilities(
            PoissonEngine::firstHalfMatrix($lambdaHome, $lambdaAway)
        );

        $this->assertEqualsWithDelta(
            1.0,
            $half['home_lead'] + $half['level'] + $half['away_lead'],
            1e-9,
            'leading, level and trailing at the break must partition the space'
        );

        $fullTime = PoissonEngine::marketProbabilities(
            PoissonEngine::scoreMatrix($lambdaHome, $lambdaAway)
        );

        // Only 45% of the expected goals fall before the break, so a first-half
        // goal must be less likely than a full-match one. A 50/50 split — or
        // reusing the full-time lambdas — would break this.
        $anyGoalAllMatch = 1.0 - (PoissonEngine::scoreMatrix($lambdaHome, $lambdaAway)[0][0]);
        $this->assertLessThan($anyGoalAllMatch, $half['over_0_5']);

        // And the side favoured over the match must be favoured at the break.
        $this->assertGreaterThan($half['away_lead'], $half['home_lead']);
        $this->assertGreaterThan($fullTime['away_win'], $fullTime['home_win']);

        // A level half-time is common; the stronger side leading is not a lock.
        $this->assertGreaterThan(0.0, $half['level']);
        $this->assertLessThan(1.0, $half['home_lead']);
    }

    public function test_a_larger_first_half_share_raises_first_half_goal_probability(): void
    {
        $quiet = PoissonEngine::halfTimeProbabilities(PoissonEngine::firstHalfMatrix(1.5, 1.2, 0.35));
        $busy = PoissonEngine::halfTimeProbabilities(PoissonEngine::firstHalfMatrix(1.5, 1.2, 0.55));

        $this->assertGreaterThan($quiet['over_0_5'], $busy['over_0_5']);
    }

    public function test_market_probabilities_are_coherent(): void
    {
        $markets = PoissonEngine::marketProbabilities(PoissonEngine::scoreMatrix(1.6, 1.2));

        $this->assertEqualsWithDelta(
            1.0,
            $markets['home_win'] + $markets['draw'] + $markets['away_win'],
            1e-9,
            'the three win/draw/loss outcomes must partition the space'
        );

        foreach (['gg', 'over_2_5'] as $market) {
            $this->assertGreaterThan(0.0, $markets[$market]);
            $this->assertLessThan(1.0, $markets[$market]);
        }
    }

    public function test_stronger_attack_raises_home_win_probability(): void
    {
        $even = PoissonEngine::marketProbabilities(PoissonEngine::scoreMatrix(1.3, 1.3));
        $strong = PoissonEngine::marketProbabilities(PoissonEngine::scoreMatrix(2.4, 0.9));

        $this->assertGreaterThan($even['home_win'], $strong['home_win']);
        $this->assertLessThan($even['away_win'], $strong['away_win']);
    }

    /**
     * The reason Dixon-Coles exists: independent Poisson materially
     * under-predicts 1-1 in low-scoring fixtures.
     */
    public function test_dixon_coles_lifts_the_one_one_scoreline(): void
    {
        $corrected = PoissonEngine::scoreMatrix(1.2, 1.1, PoissonEngine::DEFAULT_RHO);
        $independent = PoissonEngine::scoreMatrix(1.2, 1.1, 0.0);

        $this->assertGreaterThan($independent[1][1], $corrected[1][1]);
        $this->assertLessThan($independent[1][0], $corrected[1][0]);
    }

    public function test_expected_goals_reflect_league_baseline_and_home_advantage(): void
    {
        $form = ['gf' => 1.5, 'ga' => 1.5];

        $lambdas = PoissonEngine::expectedGoals($form, $form, 1.5, 1.2);

        // Two exactly average sides in a 1.5-goal league: both regress to the
        // baseline, with the home side lifted by the advantage multiplier.
        $this->assertEqualsWithDelta(1.5 * 1.2, $lambdas['home'], 1e-9);
        $this->assertEqualsWithDelta(1.5, $lambdas['away'], 1e-9);
    }

    public function test_weighted_average_favours_recent_matches(): void
    {
        // Oldest first: a side that has stopped scoring should land below the
        // arithmetic mean of 1.5.
        $weighted = PoissonEngine::weightedAverage([3.0, 3.0, 0.0, 0.0]);

        $this->assertNotNull($weighted);
        $this->assertLessThan(1.5, $weighted);
        $this->assertNull(PoissonEngine::weightedAverage([]));
    }
}
