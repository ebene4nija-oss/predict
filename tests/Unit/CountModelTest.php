<?php

namespace Tests\Unit;

use App\Support\CountModel;
use PHPUnit\Framework\TestCase;

class CountModelTest extends TestCase
{
    public function test_the_distribution_sums_to_one(): void
    {
        foreach ([[9.6, null], [9.6, 12.9], [3.7, 3.3], [3.9, 4.4]] as [$mean, $variance]) {
            $this->assertEqualsWithDelta(
                1.0,
                array_sum(CountModel::distribution($mean, $variance)),
                1e-9,
                "mean {$mean} must produce a normalised distribution"
            );
        }
    }

    /**
     * The measured Premier League corner total: mean 10.00, over 8.5 in 66.1%
     * of matches. A model that cannot reproduce its own league average is not
     * calibrated, whatever it says about an individual fixture.
     */
    public function test_it_reproduces_the_measured_league_rates(): void
    {
        $this->assertEqualsWithDelta(
            0.661,
            CountModel::overProbability(10.00, 8.5, 10.67),
            0.05,
            'Premier League corners over 8.5'
        );

        $this->assertEqualsWithDelta(
            0.708,
            CountModel::overProbability(3.75, 2.5, 3.80),
            0.05,
            'Premier League yellows over 2.5'
        );
    }

    /**
     * Overdispersion fattens both tails, so the two models cross at the mean:
     * a negative binomial puts less mass above a line *below* the mean and more
     * above one over it.
     *
     * This is why the distribution is chosen from the data rather than fixed.
     * The corners line is 8.5 against a league mean near 9.6 — below it — so
     * assuming Poisson would overprice the over by around three points on every
     * corner pick the site publishes.
     */
    public function test_overdispersion_shifts_probability_across_the_mean(): void
    {
        $mean = 9.6;
        $variance = 12.9;

        // Below the mean: the negative binomial is more conservative.
        $this->assertLessThan(
            CountModel::overProbability($mean, 8.5),
            CountModel::overProbability($mean, 8.5, $variance),
        );

        // Above it: the fatter upper tail makes the over more likely.
        $this->assertGreaterThan(
            CountModel::overProbability($mean, 11.5),
            CountModel::overProbability($mean, 11.5, $variance),
        );
    }

    /**
     * A negative binomial cannot represent variance below its mean, so an
     * underdispersed league — Serie A cards — must fall back to Poisson rather
     * than produce a division by a negative number.
     */
    public function test_underdispersed_input_falls_back_to_poisson(): void
    {
        $underdispersed = CountModel::overProbability(3.70, 2.5, 3.28);
        $poisson = CountModel::overProbability(3.70, 2.5);

        $this->assertSame($poisson, $underdispersed);
        $this->assertGreaterThan(0.0, $underdispersed);
        $this->assertLessThan(1.0, $underdispersed);
    }

    public function test_expected_total_scales_with_both_clubs(): void
    {
        // Two average sides reproduce the league total.
        $this->assertEqualsWithDelta(
            10.0,
            CountModel::expectedTotal(5.0, 5.0, 5.0, 5.0, 10.0),
            1e-9
        );

        // A high-corner side against a side that concedes corners raises it.
        $busy = CountModel::expectedTotal(7.0, 6.5, 5.0, 5.0, 10.0);
        $this->assertGreaterThan(10.0, $busy);

        // A missing rate on one side falls back to league average for that
        // side alone rather than discarding the fixture.
        $partial = CountModel::expectedTotal(null, null, 5.0, 5.0, 10.0);
        $this->assertEqualsWithDelta(10.0, $partial, 1e-9);
    }

    /**
     * A three-match sample can throw up a rate three times the league's. The
     * clamp stops one noisy club from dominating the whole fixture.
     */
    public function test_extreme_rates_are_clamped(): void
    {
        $absurd = CountModel::expectedTotal(20.0, 20.0, 5.0, 5.0, 10.0);

        $this->assertLessThanOrEqual(15.0, $absurd);
    }
}
