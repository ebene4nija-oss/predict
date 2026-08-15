<?php

namespace Tests\Unit;

use App\Support\MarketOutcome;
use PHPUnit\Framework\TestCase;

class MarketOutcomeTest extends TestCase
{
    public function test_it_reads_the_actual_outcome_of_a_score(): void
    {
        $this->assertSame([
            'win_draw_loss' => 'Home Win',
            'gg' => 'GG (Yes)',
            'over_2_5' => 'Over 2.5',
        ], MarketOutcome::actual(3, 1));

        $this->assertSame([
            'win_draw_loss' => 'Draw',
            'gg' => 'NG (No)',
            'over_2_5' => 'Under 2.5',
        ], MarketOutcome::actual(0, 0));
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

    public function test_unrecognised_or_empty_picks_never_count_as_wins(): void
    {
        $this->assertFalse(MarketOutcome::isWinningPick('gg', null, 2, 1));
        $this->assertFalse(MarketOutcome::isWinningPick('gg', '', 2, 1));
        $this->assertFalse(MarketOutcome::isWinningPick('gg', 'maybe', 2, 1));
        $this->assertFalse(MarketOutcome::isWinningPick('unknown_market', 'Yes', 2, 1));
    }
}
