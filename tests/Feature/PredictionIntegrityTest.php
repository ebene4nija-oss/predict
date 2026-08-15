<?php

namespace Tests\Feature;

use App\Models\GameMatch;
use App\Models\Prediction;
use App\Models\Setting;
use App\Models\User;
use App\Services\PredictionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PredictionIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function fixture(string $kickoff = '+6 hours'): GameMatch
    {
        return GameMatch::create([
            'home_team' => 'Arsenal',
            'away_team' => 'Chelsea',
            'league' => 'Premier League',
            'kickoff_at' => now()->modify($kickoff),
            'home_form' => ['gf' => 1.2, 'ga' => 1.3],
            'away_form' => ['gf' => 1.3, 'ga' => 1.2],
        ]);
    }

    /**
     * The regression this whole change exists for: the engine used to apply
     * max($threshold, $p), so an evenly-matched fixture reported 55% for every
     * market regardless of what the model actually computed.
     */
    public function test_probabilities_are_not_inflated_to_the_confidence_threshold(): void
    {
        Setting::set('min_confidence_threshold', '0.55');
        Setting::set('prediction_provider', 'poisson_xg');

        $match = $this->fixture();

        app(PredictionService::class)->calculateAndStore($match);

        $wdl = Prediction::where('match_id', $match->id)->where('market', 'win_draw_loss')->first();

        // Two evenly matched sides cannot produce a 55%+ favourite once the
        // draw takes its share.
        $this->assertLessThan(0.55, $wdl->probability);
        $this->assertGreaterThan(0.0, $wdl->probability);
    }

    public function test_every_stored_prediction_is_stamped_and_attributed(): void
    {
        Setting::set('prediction_provider', 'poisson_xg');

        $match = $this->fixture();

        app(PredictionService::class)->calculateAndStore($match);

        foreach (Prediction::where('match_id', $match->id)->get() as $prediction) {
            $this->assertNotNull($prediction->published_at, 'a pick must record when it was locked');
            $this->assertSame('poisson_xg', $prediction->source);
            $this->assertTrue($prediction->published_at->lessThanOrEqualTo($match->kickoff_at));
        }
    }

    public function test_a_started_fixture_is_not_repredicted(): void
    {
        Setting::set('prediction_provider', 'poisson_xg');

        $match = $this->fixture('-2 hours');

        $stored = app(PredictionService::class)->calculateAndStore($match);

        $this->assertSame([], $stored);
        $this->assertDatabaseCount('predictions', 0);
    }

    public function test_admin_cannot_edit_a_prediction_after_kickoff(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $match = $this->fixture('-3 hours');

        $prediction = Prediction::create([
            'match_id' => $match->id,
            'market' => 'win_draw_loss',
            'pick' => 'Draw',
            'probability' => 0.30,
            'published_at' => $match->kickoff_at->copy()->subHour(),
        ]);

        $this->actingAs($admin)
            ->put("/admin/predictions/{$prediction->id}", [
                'pick' => 'Home Win',
                'probability' => 0.99,
            ])
            ->assertRedirect();

        $prediction->refresh();
        $this->assertSame('Draw', $prediction->pick, 'a settled-era pick must not be rewritable');
        $this->assertSame(0.30, $prediction->probability);
    }

    public function test_picks_below_the_threshold_are_not_promoted(): void
    {
        Setting::set('min_confidence_threshold', '0.80');

        $match = $this->fixture();

        $low = Prediction::create([
            'match_id' => $match->id,
            'market' => 'win_draw_loss',
            'pick' => 'Home Win',
            'probability' => 0.52,
            'published_at' => now(),
        ]);

        $high = Prediction::create([
            'match_id' => $match->id,
            'market' => 'gg',
            'pick' => 'GG (Yes)',
            'probability' => 0.85,
            'published_at' => now(),
        ]);

        (new \App\Jobs\RankingJob)->handle();
        (new \App\Jobs\Ai5SelectionJob)->handle();

        $this->assertFalse($low->fresh()->is_top10);
        $this->assertTrue($high->fresh()->is_top10);
        $this->assertTrue($high->fresh()->is_ai5);

        // Crucially, the low pick keeps its honest probability rather than
        // being lifted to the threshold.
        $this->assertSame(0.52, $low->fresh()->probability);
    }
}
