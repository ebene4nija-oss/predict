<?php

namespace Tests\Unit;

use App\Support\MarketOutcome;
use PHPUnit\Framework\TestCase;

class MarketOutcomeTest extends TestCase
{
    public function test_it_reads_the_actual_outcome_of_a_score(): void
    {
        $this->assertSame([
            'win' => 'Home Win',
            'over_2_5' => 'Over 2.5',
            'gg' => 'GG (Yes)',
            'win_draw_loss' => 'Home Win',
        ], MarketOutcome::actual(3, 1));

        $this->assertSame([
            'win' => null,
            'over_2_5' => 'Under 2.5',
            'gg' => 'NG (No)',
            'win_draw_loss' => 'Draw',
        ], MarketOutcome::actual(0, 0));
    }

    /**
     * Markets needing data the result does not carry are absent, not guessed —
     * the caller has to be able to tell "unsettleable" from "settled".
     */
    public function test_markets_without_their_settle_data_are_omitted(): void
    {
        $actual = MarketOutcome::actual(2, 1);

        $this->assertArrayNotHasKey('ht_win', $actual);
        $this->assertArrayNotHasKey('corners_over_8_5', $actual);

        $this->assertFalse(MarketOutcome::isDeterminable('ht_win', []));
        $this->assertTrue(MarketOutcome::isDeterminable('ht_win', ['ht_home' => 1, 'ht_away' => 0]));

        $this->assertSame(
            'HT Home Win',
            MarketOutcome::actual(2, 1, ['ht_home' => 1, 'ht_away' => 0])['ht_win']
        );
    }

    /**
     * The win market excludes the draw, so a drawn match settles it with no
     * winning selection — and an unparseable pick must not match that.
     */
    public function test_a_draw_loses_the_win_market_without_voiding_it(): void
    {
        $this->assertFalse(MarketOutcome::isWinningPick('win', 'Home Win', 1, 1));
        $this->assertFalse(MarketOutcome::isWinningPick('win', 'Away Win', 1, 1));
        $this->assertFalse(MarketOutcome::isWinningPick('win', 'nonsense', 1, 1));

        $this->assertTrue(MarketOutcome::isWinningPick('win', 'Home Win', 2, 1));
        $this->assertTrue(MarketOutcome::isWinningPick('win', 'Away Win', 0, 3));

        // Settled, so the pick is graded — as a loss, not skipped.
        $this->assertTrue(MarketOutcome::isDeterminable('win', []));
        $this->assertArrayHasKey('win', MarketOutcome::actual(1, 1));
    }

    public function test_a_goalless_draw_is_graded_as_a_draw(): void
    {
        $this->assertTrue(MarketOutcome::isWinningPick('win_draw_loss', 'Draw', 0, 0));
        $this->assertTrue(MarketOutcome::isWinningPick('win_draw_loss', 'Draw', 2, 2));
        $this->assertFalse(MarketOutcome::isWinningPick('win_draw_loss', 'Home Win', 2, 2));
    }

    /**
     * The regression that motivated this class: the same pick was graded one
     * way on the leaderboard and another on the track record.
     */
    public function test_equivalent_pick_spellings_grade_identically(): void
    {
        foreach (['GG (Yes)', 'Yes', 'gg', 'BTTS', 'both teams to score'] as $spelling) {
            $this->assertTrue(
                MarketOutcome::isWinningPick('gg', $spelling, 2, 1),
                "'{$spelling}' should win when both teams scored"
            );
            $this->assertFalse(
                MarketOutcome::isWinningPick('gg', $spelling, 2, 0),
                "'{$spelling}' should lose when only one team scored"
            );
        }

        foreach (['Home Win', 'home', '1'] as $spelling) {
            $this->assertTrue(MarketOutcome::isWinningPick('win_draw_loss', $spelling, 2, 0));
        }

        foreach (['Over 2.5', 'over', 'O2.5'] as $spelling) {
            $this->assertTrue(MarketOutcome::isWinningPick('over_2_5', $spelling, 2, 1));
            $this->assertFalse(MarketOutcome::isWinningPick('over_2_5', $spelling, 1, 1));
        }
    }

    /**
     * The half-time markets settle from the half-time score alone, not the
     * full-time one — a match won 3-0 after a goalless first half loses both.
     */
    public function test_half_time_markets_settle_from_the_half_time_score(): void
    {
        $goallessHalf = ['ht_home' => 0, 'ht_away' => 0];

        $this->assertFalse(MarketOutcome::isWinningPick('fh_over_0_5', '1H Over 0.5', 3, 0, $goallessHalf));
        $this->assertTrue(MarketOutcome::isWinningPick('fh_over_0_5', '1H Under 0.5', 3, 0, $goallessHalf));
        $this->assertFalse(MarketOutcome::isWinningPick('ht_win', 'HT Home Win', 3, 0, $goallessHalf));

        $leadingHalf = ['ht_home' => 1, 'ht_away' => 0];

        $this->assertTrue(MarketOutcome::isWinningPick('fh_over_0_5', '1H Over 0.5', 1, 2, $leadingHalf));
        $this->assertTrue(MarketOutcome::isWinningPick('ht_win', 'HT Home Win', 1, 2, $leadingHalf));
        $this->assertFalse(MarketOutcome::isWinningPick('ht_win', 'HT Away Win', 1, 2, $leadingHalf));
    }

    /**
     * A fixture settled without a half-time score must leave those markets
     * unsettled. Grading them against a missing value would report a goalless
     * first half for every historical result.
     */
    public function test_a_missing_half_time_score_leaves_those_markets_unsettled(): void
    {
        $actual = MarketOutcome::actual(2, 1);

        $this->assertArrayNotHasKey('fh_over_0_5', $actual);
        $this->assertArrayNotHasKey('ht_win', $actual);
        $this->assertFalse(MarketOutcome::isDeterminable('fh_over_0_5', []));

        // Present but only half-supplied is still not settleable.
        $this->assertFalse(MarketOutcome::isDeterminable('ht_win', ['ht_home' => 1]));
    }

    public function test_unrecognised_or_empty_picks_never_count_as_wins(): void
    {
        $this->assertFalse(MarketOutcome::isWinningPick('gg', null, 2, 1));
        $this->assertFalse(MarketOutcome::isWinningPick('gg', '', 2, 1));
        $this->assertFalse(MarketOutcome::isWinningPick('gg', 'maybe', 2, 1));
        $this->assertFalse(MarketOutcome::isWinningPick('unknown_market', 'Yes', 2, 1));
    }
}
