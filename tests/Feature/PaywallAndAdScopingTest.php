<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Subscription;
use App\Models\GameMatch;
use App\Models\Prediction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaywallAndAdScopingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed matches & predictions for top-picks testing
        for ($i = 1; $i <= 5; $i++) {
            $match = GameMatch::create([
                'home_team' => "Team Home {$i}",
                'away_team' => "Team Away {$i}",
                'league' => 'Premier League',
                'kickoff_at' => now()->addHours($i),
            ]);

            Prediction::create([
                'match_id' => $match->id,
                'market' => 'win_draw_loss',
                'pick' => 'Home Win',
                'probability' => 0.90 - ($i * 0.05),
                'is_top10' => true,
                'is_ai5' => $i <= 5,
            ]);
        }
    }

    public function test_free_user_sees_ad_banner_and_locked_samples(): void
    {
        $freeUser = User::factory()->create(['role' => 'free']);

        $response = $this->actingAs($freeUser)->get('/top-picks');
        $response->assertStatus(200);
        $response->assertSee('Sponsored Advertisement');
        $response->assertSee('Gated for Pro Subscribers');
        $response->assertSee('Unlock Now');
    }

    public function test_active_subscriber_sees_zero_ads_and_unlocked_content(): void
    {
        $subscriber = User::factory()->create(['role' => 'subscriber']);
        Subscription::create([
            'user_id' => $subscriber->id,
            'gateway' => 'flutterwave',
            'status' => 'active',
            'plan' => 'monthly_pro',
            'renews_at' => now()->addMonth(),
        ]);

        $response = $this->actingAs($subscriber)->get('/top-picks');
        $response->assertStatus(200);
        $response->assertDontSee('Sponsored Advertisement');
        $response->assertDontSee('Gated for Pro Subscribers');
    }
}
