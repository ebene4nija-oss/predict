<?php

namespace Tests\Feature;

use App\Models\GameMatch;
use App\Models\Prediction;
use App\Models\User;
use App\Services\TrackRecordService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackRecordCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_settling_a_match_refreshes_the_published_accuracy_figures(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $match = GameMatch::create([
            'home_team' => 'Arsenal',
            'away_team' => 'Chelsea',
            'league' => 'Premier League',
            'kickoff_at' => now()->subHours(3),
        ]);

        Prediction::create([
            'match_id' => $match->id,
            'market' => 'win_draw_loss',
            'pick' => 'Home Win',
            'probability' => 0.7,
            'is_top10' => true,
            'is_ai5' => true,
            'published_at' => $match->kickoff_at->copy()->subHour(),
        ]);

        $service = app(TrackRecordService::class);

        // Warm the cache while nothing is settled.
        $this->assertSame(0, $service->getAccuracyStats()['ai']['overall']['total']);

        $this->actingAs($admin)
            ->post("/admin/matches/{$match->id}/settle", ['home_score' => 2, 'away_score' => 0])
            ->assertRedirect(route('admin.matches.index'));

        $stats = $service->getAccuracyStats();

        $this->assertSame(1, $stats['ai']['overall']['total'], 'settling must invalidate the cached figures');
        $this->assertSame(1, $stats['ai']['overall']['won']);
        $this->assertSame(100.0, $stats['ai']['win_draw_loss']['rate']);
    }

    public function test_a_draw_is_graded_correctly_end_to_end(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $match = GameMatch::create([
            'home_team' => 'Arsenal',
            'away_team' => 'Chelsea',
            'league' => 'Premier League',
            'kickoff_at' => now()->subHours(3),
        ]);

        Prediction::create([
            'match_id' => $match->id,
            'market' => 'win_draw_loss',
            'pick' => 'Draw',
            'probability' => 0.4,
            'is_top10' => false,
            'is_ai5' => false,
            'published_at' => $match->kickoff_at->copy()->subHour(),
        ]);

        $this->actingAs($admin)
            ->post("/admin/matches/{$match->id}/settle", ['home_score' => 1, 'away_score' => 1]);

        $stats = app(TrackRecordService::class)->getAccuracyStats();

        $this->assertSame(1, $stats['ai']['win_draw_loss']['won'], 'a 1-1 must grade a Draw pick as won');
    }
}
