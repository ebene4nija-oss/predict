<?php

namespace Tests\Feature;

use App\Models\GameMatch;
use App\Models\Prediction;
use App\Models\Setting;
use App\Models\User;
use App\Services\SportyBetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SportyBetBookingCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function createTop5Picks(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $match = GameMatch::create([
                'home_team' => "Home Team {$i}",
                'away_team' => "Away Team {$i}",
                'league' => 'Premier League',
                'kickoff_at' => now()->addHours($i + 1),
                'status' => 'TIMED',
            ]);

            Prediction::create([
                'match_id' => $match->id,
                'market' => 'win',
                'pick' => 'Home Win',
                'probability' => 0.75,
                'odds' => 1.50,
                'is_ai5' => true,
                'is_top10' => true,
                'published_at' => now(),
            ]);
        }
    }

    public function test_sportybet_service_calculates_total_odds_and_generates_booking_code(): void
    {
        $this->createTop5Picks();

        $service = app(SportyBetService::class);
        $payload = $service->getTop5BookingCodePayload();

        $this->assertTrue($payload['enabled']);
        $this->assertEquals(5, $payload['picks_count']);
        $this->assertNotEmpty($payload['code']);
        $this->assertEquals(6, strlen($payload['code']));
        // 1.50^5 = 7.59
        $this->assertEquals(7.59, $payload['total_odds']);
        $this->assertEquals('ng', $payload['region']);
        $this->assertEquals('Nigeria', $payload['region_name']);
        $this->assertStringContainsString('shareCode=' . $payload['code'], $payload['load_url']);
    }

    public function test_sportybet_service_respects_custom_booking_code_and_region(): void
    {
        $this->createTop5Picks();

        Setting::set('sportybet_top5_booking_code', 'SB-WINNERS-2026');
        Setting::set('sportybet_region', 'gh');

        $service = app(SportyBetService::class);
        $payload = $service->getTop5BookingCodePayload();

        $this->assertEquals('SB-WINNERS-2026', $payload['code']);
        $this->assertTrue($payload['is_custom']);
        $this->assertEquals('gh', $payload['region']);
        $this->assertEquals('Ghana', $payload['region_name']);
        $this->assertStringContainsString('https://www.sportybet.com/gh/', $payload['load_url']);
    }

    public function test_homepage_renders_sportybet_booking_code_and_copy_button(): void
    {
        $this->createTop5Picks();
        Setting::set('sportybet_top5_booking_code', 'SPB-TOP5-PRO');

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('SPB-TOP5-PRO');
        $response->assertSee('Copy SportyBet Code');
        $response->assertSee('SportyBet Nigeria Slip');
        $response->assertSee('Top 5 AI Conviction Booking Code');
    }

    public function test_top_picks_page_renders_sportybet_booking_code(): void
    {
        $this->createTop5Picks();
        Setting::set('sportybet_top5_booking_code', 'SPB-TOP5-VIP');

        $response = $this->get(route('top.picks'));

        $response->assertOk();
        $response->assertSee('SPB-TOP5-VIP');
        $response->assertSee('Copy SportyBet Code');
    }

    public function test_admin_can_update_sportybet_settings_and_credentials(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.settings.update'), [
            'fixture_provider' => 'football_data',
            'paypal_mode' => 'sandbox',
            'prediction_provider' => 'claude',
            'min_confidence_threshold' => 0.55,
            'home_advantage' => 1.25,
            'default_league_average' => 1.35,
            'sportybet_booking_present' => '1',
            'sportybet_booking_code_enabled' => '1',
            'sportybet_top5_booking_code' => 'SB-NINJA-99',
            'sportybet_region' => 'ke',
            'sportybet_cookie' => 'session_cookie_secret_token_123',
            'sportybet_token' => 'x_token_abc_xyz',
        ]);

        $response->assertRedirect(route('admin.settings'));

        $this->assertEquals('SB-NINJA-99', Setting::get('sportybet_top5_booking_code'));
        $this->assertEquals('ke', Setting::get('sportybet_region'));
        $this->assertEquals('session_cookie_secret_token_123', Setting::get('sportybet_cookie'));
        $this->assertEquals('x_token_abc_xyz', Setting::get('sportybet_token'));
    }

    public function test_disabling_sportybet_booking_code_hides_it_from_pages(): void
    {
        $this->createTop5Picks();
        Setting::set('sportybet_booking_code_enabled', '0');
        Setting::set('sportybet_top5_booking_code', 'HIDDEN-CODE-123');

        $homeResponse = $this->get(route('home'));
        $homeResponse->assertDontSee('HIDDEN-CODE-123');
        $homeResponse->assertDontSee('Copy SportyBet Code');

        $topPicksResponse = $this->get(route('top.picks'));
        $topPicksResponse->assertDontSee('HIDDEN-CODE-123');
        $topPicksResponse->assertDontSee('Copy SportyBet Code');
    }
}
