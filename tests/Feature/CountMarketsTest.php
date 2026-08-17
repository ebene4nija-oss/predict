<?php

namespace Tests\Feature;

use App\Jobs\MatchStatsIngestionJob;
use App\Models\GameMatch;
use App\Models\Prediction;
use App\Models\Result;
use App\Models\Team;
use App\Services\PredictionService;
use App\Services\Stats\FootballDataCoUkClient;
use App\Services\Stats\MatchStatsService;
use App\Support\MarketOutcome;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The corner and card markets end to end: CSV in, rates and settled totals out,
 * and the rule that a club with no rates produces no pick.
 */
class CountMarketsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /**
     * A CSV shaped like the real one, with the columns the parser reads.
     */
    protected function fakeCsv(int $matchdays = 12): void
    {
        $header = 'Div,Date,Time,HomeTeam,AwayTeam,FTHG,FTAG,FTR,HTHG,HTAG,HTR,HS,AS,HC,AC,HY,AY,HR,AR';
        $lines = [$header];

        // Two clubs meeting repeatedly, so both clear the minimum sample. Man
        // United wins a lot of corners; Everton concedes a lot.
        for ($i = 0; $i < $matchdays; $i++) {
            $date = now()->subDays(7 * ($i + 1))->format('d/m/Y');
            $lines[] = "E0,{$date},15:00,Man United,Everton,2,1,H,1,0,H,15,8,8,3,2,1,0,0";
            $lines[] = "E0,{$date},15:00,Everton,Man United,0,1,A,0,0,D,9,14,4,7,3,2,0,0";
        }

        // Only the current season file exists. The client always asks for the
        // previous one too, and answering both would silently double every
        // club's sample.
        $season = FootballDataCoUkClient::seasonCodes()[0];

        Http::fake([
            "*/{$season}/*" => Http::response(implode("\n", $lines)),
            '*football-data.co.uk*' => Http::response('', 404),
        ]);
    }

    protected function seedClubs(): array
    {
        $home = Team::create(['name' => 'Manchester United FC', 'provider' => 'football-data', 'external_id' => '66']);
        $away = Team::create(['name' => 'Everton FC', 'provider' => 'football-data', 'external_id' => '62']);

        return [$home, $away];
    }

    protected function fixture(Team $home, Team $away, string $when = '+2 days'): GameMatch
    {
        return GameMatch::create([
            'home_team' => 'Manchester United FC',
            'away_team' => 'Everton FC',
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'league' => 'Premier League',
            'kickoff_at' => now()->modify($when),
            'home_form' => ['gf' => 2.0, 'ga' => 1.0],
            'away_form' => ['gf' => 1.0, 'ga' => 1.8],
        ]);
    }

    public function test_team_rates_are_derived_from_the_stats_csv(): void
    {
        [$home, $away] = $this->seedClubs();
        $this->fixture($home, $away);
        $this->fakeCsv();

        $updated = app(MatchStatsService::class)->refreshTeamRates();

        $this->assertSame(2, $updated);

        $home->refresh();
        $away->refresh();

        // Man United: 8 corners at home, 7 away → 7.5 per game.
        $this->assertEqualsWithDelta(7.5, $home->corners_for, 0.01);
        $this->assertEqualsWithDelta(3.5, $home->corners_against, 0.01);
        $this->assertTrue($home->hasCountStats());

        // The CSV name it matched is recorded, so a bad match is auditable.
        $this->assertSame('Man United', $home->stats_alias);
        $this->assertSame('Everton', $away->stats_alias);
    }

    public function test_a_club_below_the_minimum_sample_gets_no_rates(): void
    {
        [$home, $away] = $this->seedClubs();
        $this->fixture($home, $away);

        // Three matchdays is well under MIN_SAMPLE.
        $this->fakeCsv(matchdays: 2);

        app(MatchStatsService::class)->refreshTeamRates();

        $this->assertNull($home->refresh()->corners_for);
        $this->assertFalse($home->hasCountStats());
    }

    public function test_count_markets_are_produced_only_when_both_clubs_have_rates(): void
    {
        [$home, $away] = $this->seedClubs();
        $match = $this->fixture($home, $away);
        $this->fakeCsv();

        // Before rates exist: no corner or card pick.
        app(PredictionService::class)->calculateAndStore($match);
        $this->assertDatabaseMissing('predictions', ['match_id' => $match->id, 'market' => 'corners_over_8_5']);

        app(MatchStatsService::class)->refreshTeamRates();
        Prediction::where('match_id', $match->id)->delete();

        app(PredictionService::class)->calculateAndStore($match->fresh());

        $corners = Prediction::where('match_id', $match->id)->where('market', 'corners_over_8_5')->first();
        $cards = Prediction::where('match_id', $match->id)->where('market', 'cards_over_2_5')->first();

        $this->assertNotNull($corners);
        $this->assertNotNull($cards);
        $this->assertContains($corners->pick, ['Over 8.5 Corners', 'Under 8.5 Corners']);
        $this->assertGreaterThan(0.5, $corners->probability, 'the published probability is that of the tipped side');
    }

    /**
     * The Champions League gap. The CSVs are domestic, so a fixture involving a
     * club with no file gets every other market and neither count market —
     * rather than a corner pick built on a league average.
     */
    public function test_a_fixture_with_an_unmapped_club_gets_no_count_markets(): void
    {
        [$home] = $this->seedClubs();
        $european = Team::create(['name' => 'Galatasaray SK', 'provider' => 'football-data', 'external_id' => '999']);

        $match = GameMatch::create([
            'home_team' => 'Manchester United FC',
            'away_team' => 'Galatasaray SK',
            'home_team_id' => $home->id,
            'away_team_id' => $european->id,
            'league' => 'UEFA Champions League',
            'kickoff_at' => now()->addDays(2),
            'home_form' => ['gf' => 2.0, 'ga' => 1.0],
            'away_form' => ['gf' => 1.3, 'ga' => 1.4],
        ]);

        $this->fakeCsv();
        app(MatchStatsService::class)->refreshTeamRates();

        app(PredictionService::class)->calculateAndStore($match->fresh());

        $markets = Prediction::where('match_id', $match->id)->pluck('market');

        $this->assertContains('win', $markets->all());
        $this->assertContains('ht_win', $markets->all());
        $this->assertNotContains('corners_over_8_5', $markets->all());
        $this->assertNotContains('cards_over_2_5', $markets->all());
    }

    public function test_finished_fixtures_are_settled_with_corner_and_card_totals(): void
    {
        [$home, $away] = $this->seedClubs();
        $match = $this->fixture($home, $away, '-7 days');

        // The stats pass fills a result the score ingestion already created.
        Result::create([
            'match_id' => $match->id,
            'home_score' => 2,
            'away_score' => 1,
            'settled_at' => now(),
        ]);

        $this->fakeCsv();

        app(MatchStatsIngestionJob::class)->handle(
            app(MatchStatsService::class),
            app(\App\Services\TrackRecordService::class),
        );

        $result = Result::where('match_id', $match->id)->firstOrFail();

        $this->assertSame(8, $result->home_corners);
        $this->assertSame(3, $result->away_corners);

        // The stored outcome snapshot is re-derived on save, so it picks up the
        // count markets that the earlier score-only settle could not know about.
        $this->assertSame('Over 8.5 Corners', $result->actual_outcome['corners_over_8_5']);
        $this->assertSame('Over 2.5 Cards', $result->actual_outcome['cards_over_2_5']);

        $this->assertSame(11, $result->gradingContext()['corners']);
        $this->assertTrue(MarketOutcome::isWinningPick(
            'corners_over_8_5', 'Over 8.5 Corners', 2, 1, $result->gradingContext()
        ));
    }

    public function test_a_result_without_count_data_leaves_those_markets_unsettled(): void
    {
        [$home, $away] = $this->seedClubs();
        $match = $this->fixture($home, $away, '-7 days');

        $result = Result::create([
            'match_id' => $match->id,
            'home_score' => 2,
            'away_score' => 1,
            'settled_at' => now(),
        ]);

        $this->assertArrayNotHasKey('corners_over_8_5', $result->actual_outcome);
        $this->assertFalse(MarketOutcome::isDeterminable('corners_over_8_5', $result->gradingContext()));
    }

    public function test_the_league_baseline_lookup_covers_the_configured_competitions(): void
    {
        $this->assertSame('E0', FootballDataCoUkClient::divisionFor('Premier League'));
        $this->assertSame('SP1', FootballDataCoUkClient::divisionFor('La Liga'));
        $this->assertSame('I1', FootballDataCoUkClient::divisionFor('Serie A'));
        $this->assertSame('D1', FootballDataCoUkClient::divisionFor('Bundesliga'));
        $this->assertSame('F1', FootballDataCoUkClient::divisionFor('Ligue 1'));

        // No domestic file exists for it, which is the whole Champions League gap.
        $this->assertNull(FootballDataCoUkClient::divisionFor('UEFA Champions League'));
    }

    public function test_season_codes_span_the_summer_boundary(): void
    {
        $this->assertSame(
            ['2627', '2526'],
            FootballDataCoUkClient::seasonCodes(\Carbon\Carbon::parse('2026-08-16'))
        );

        // January belongs to the season that started the previous year.
        $this->assertSame(
            ['2526', '2425'],
            FootballDataCoUkClient::seasonCodes(\Carbon\Carbon::parse('2026-01-20'))
        );
    }
}
