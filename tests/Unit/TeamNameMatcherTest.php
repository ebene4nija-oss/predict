<?php

namespace Tests\Unit;

use App\Support\TeamNameMatcher;
use PHPUnit\Framework\TestCase;

/**
 * The fixture provider and the stats CSV name the same clubs differently, and
 * every corner and card pick depends on reconciling them.
 */
class TeamNameMatcherTest extends TestCase
{
    public static function clubPairs(): array
    {
        return [
            // Suffix and abbreviation differences.
            ['Manchester United FC', 'Man United'],
            ['Manchester City FC', 'Man City'],
            ['West Ham United FC', 'West Ham'],
            ['Tottenham Hotspur FC', 'Tottenham'],
            ['Wolverhampton Wanderers FC', 'Wolves'],
            ['Nottingham Forest FC', "Nott'm Forest"],
            ['Brighton & Hove Albion FC', 'Brighton'],
            ['Newcastle United FC', 'Newcastle'],

            // Accents and continental prefixes.
            ['FC Bayern München', 'Bayern Munich'],
            ['Bayer 04 Leverkusen', 'Leverkusen'],
            ['Eintracht Frankfurt', 'Ein Frankfurt'],
            ['Borussia Dortmund', 'Dortmund'],
            ['Paris Saint-Germain FC', 'Paris SG'],
            ['Olympique de Marseille', 'Marseille'],
            ['FC Barcelona', 'Barcelona'],
            ['Club Atlético de Madrid', 'Ath Madrid'],
            ['Real Sociedad de Fútbol', 'Sociedad'],
            ['FC Internazionale Milano', 'Inter'],
            ['Juventus FC', 'Juventus'],
        ];
    }

    /**
     * @dataProvider clubPairs
     */
    public function test_it_reconciles_the_two_naming_conventions(string $provider, string $csv): void
    {
        $this->assertTrue(
            TeamNameMatcher::matches($provider, $csv),
            "'{$provider}' and '{$csv}' are the same club"
        );
    }

    /**
     * The failure that would matter: attaching one club's corner rate to
     * another's. Clubs from the same city are the obvious trap.
     */
    public function test_it_does_not_conflate_different_clubs(): void
    {
        $pairs = [
            ['Manchester United FC', 'Man City'],
            ['Real Madrid CF', 'Ath Madrid'],
            ['AC Milan', 'Inter'],
            ['Sheffield United FC', 'Sheffield Wednesday'],
            ['Nottingham Forest FC', 'Norwich City'],
            ['West Ham United FC', 'West Brom'],
        ];

        foreach ($pairs as [$a, $b]) {
            $this->assertFalse(
                TeamNameMatcher::matches($a, $b),
                "'{$a}' and '{$b}' are different clubs"
            );
        }
    }

    public function test_best_match_returns_the_key_and_null_when_nothing_fits(): void
    {
        $candidates = ['a' => 'Man United', 'b' => 'Man City', 'c' => 'Liverpool'];

        $this->assertSame('a', TeamNameMatcher::bestMatch('Manchester United FC', $candidates));
        $this->assertSame('c', TeamNameMatcher::bestMatch('Liverpool FC', $candidates));

        // A Champions League side with no domestic CSV row must not be forced
        // onto the nearest name.
        $this->assertNull(TeamNameMatcher::bestMatch('Galatasaray SK', $candidates));
    }
}
