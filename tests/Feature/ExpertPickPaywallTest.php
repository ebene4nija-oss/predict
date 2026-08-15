<?php

namespace Tests\Feature;

use App\Models\Expert;
use App\Models\ExpertPick;
use App\Models\GameMatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpertPickPaywallTest extends TestCase
{
    use RefreshDatabase;

    protected function seedPicks(int $count): void
    {
        $expert = Expert::create(['name' => 'Pundit Pete', 'bio' => 'Knows ball.']);

        for ($i = 1; $i <= $count; $i++) {
            $match = GameMatch::create([
                'home_team' => "Home {$i}",
                'away_team' => "Away {$i}",
                'league' => 'Premier League',
                'kickoff_at' => now()->addHours($i),
            ]);

            ExpertPick::create([
                'expert_id' => $expert->id,
                'match_id' => $match->id,
                'market' => 'gg',
                'pick' => 'GG (Yes)',
                'rationale' => "Secret reasoning {$i}",
                'confidence' => 0.8,
            ]);
        }
    }

    /**
     * The list is paginated, and gating must follow the pick's absolute
     * position — a page-relative index would unlock the first two picks again
     * on every page and give away the whole list for free.
     */
    public function test_free_user_cannot_page_past_the_paywall(): void
    {
        $this->seedPicks(25);

        $freeUser = User::factory()->create(['role' => 'free']);

        $response = $this->actingAs($freeUser)->get('/expert-picks?page=2');

        $response->assertOk();
        $response->assertSee('Unlock Expert Picks');

        // Every pick on page 2 sits beyond the two free samples, so none of
        // the gated rationales may leak.
        foreach (range(1, 5) as $i) {
            $response->assertDontSee("Secret reasoning {$i}");
        }
    }

    public function test_free_user_sees_only_the_first_two_picks_unlocked(): void
    {
        $this->seedPicks(5);

        $freeUser = User::factory()->create(['role' => 'free']);

        $response = $this->actingAs($freeUser)->get('/expert-picks');

        $response->assertOk();
        // Picks are ordered newest first, so #5 and #4 are the free samples.
        $response->assertSee('Secret reasoning 5');
        $response->assertSee('Secret reasoning 4');
        $response->assertDontSee('Secret reasoning 3');
        $response->assertDontSee('Secret reasoning 1');
    }

    public function test_subscriber_sees_every_pick(): void
    {
        $this->seedPicks(5);

        $subscriber = User::factory()->subscriber()->create();

        $response = $this->actingAs($subscriber)->get('/expert-picks');

        $response->assertOk();
        foreach (range(1, 5) as $i) {
            $response->assertSee("Secret reasoning {$i}");
        }
    }
}
