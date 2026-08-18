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
                'market' => 'win',
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

    /**
     * The free allowance is the product rule the whole paywall rests on, so it
     * is asserted at the boundary rather than inferred from "something is
     * locked": rank 3 open, rank 4 not.
     */
    public function test_a_free_user_sees_exactly_the_first_three_ranked_picks(): void
    {
        $freeUser = User::factory()->create(['role' => 'free']);

        // The AI Top 5 hero is deliberately ungated, and would otherwise put
        // every seeded fixture on the page regardless of the ranked list.
        Prediction::query()->update(['is_ai5' => false]);

        $response = $this->actingAs($freeUser)->get('/top-picks');

        $response->assertOk();
        $response->assertSee('Team Home 1');
        $response->assertSee('Team Home 3');
        $response->assertDontSee('Team Home 4');
        $response->assertDontSee('Team Home 5');
    }

    public function test_active_subscriber_sees_zero_ads_and_unlocked_content(): void
    {
        $subscriber = User::factory()->subscriber()->create();
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

    public function test_free_user_or_guest_cannot_see_calculated_probabilities_on_match_preview(): void
    {
        $match = GameMatch::first();
        $freeUser = User::factory()->create(['role' => 'free']);

        // Guest check
        $guestResponse = $this->get(route('matches.show', $match));
        $guestResponse->assertOk();
        $guestResponse->assertSee('Calculated Probabilities & xG Models Gated');
        $guestResponse->assertSee('Upgrade to PRO to Unlock Calculated Probabilities');
        $guestResponse->assertDontSee('GUARANTEED CORRECT CALCULATED PROBABILITIES');

        // Free authenticated user check
        $authResponse = $this->actingAs($freeUser)->get(route('matches.show', $match));
        $authResponse->assertOk();
        $authResponse->assertSee('Calculated Probabilities & xG Models Gated');
        $authResponse->assertSee('Upgrade to PRO to Unlock Calculated Probabilities');
        $authResponse->assertDontSee('GUARANTEED CORRECT CALCULATED PROBABILITIES');
    }

    public function test_active_subscriber_or_admin_can_see_calculated_probabilities_on_match_preview(): void
    {
        $match = GameMatch::first();
        $subscriber = User::factory()->subscriber()->create();
        Subscription::create([
            'user_id' => $subscriber->id,
            'gateway' => 'flutterwave',
            'status' => 'active',
            'plan' => 'monthly_pro',
            'renews_at' => now()->addMonth(),
        ]);

        $response = $this->actingAs($subscriber)->get(route('matches.show', $match));
        $response->assertOk();
        $response->assertSee('GUARANTEED CORRECT CALCULATED PROBABILITIES');
        $response->assertDontSee('Calculated Probabilities & xG Models Gated', false);
    }
}
