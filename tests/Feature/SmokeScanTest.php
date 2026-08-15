<?php

namespace Tests\Feature;

use App\Models\Expert;
use App\Models\ExpertPick;
use App\Models\GameMatch;
use App\Models\Prediction;
use App\Models\Result;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeScanTest extends TestCase
{
    use RefreshDatabase;

    protected function seedDomain(): GameMatch
    {
        $match = GameMatch::create([
            'home_team' => 'Arsenal',
            'away_team' => 'Chelsea',
            'league' => 'Premier League',
            'kickoff_at' => now()->addDay(),
            'home_form' => ['W', 'W', 'D'],
            'away_form' => ['L', 'D', 'W'],
            'h2h_summary' => 'Arsenal won 3 of last 5.',
            'injury_notes' => 'None.',
            'preview_text' => 'A big London derby.',
        ]);

        foreach (['win_draw_loss', 'gg', 'over_2_5'] as $i => $market) {
            Prediction::create([
                'match_id' => $match->id,
                'market' => $market,
                'pick' => 'Home',
                'probability' => 0.7,
                'is_top10' => true,
                'is_ai5' => $i === 0,
                'rationale' => 'Because reasons.',
            ]);
        }

        $past = GameMatch::create([
            'home_team' => 'Liverpool',
            'away_team' => 'Everton',
            'league' => 'Premier League',
            'kickoff_at' => now()->subDays(3),
        ]);
        Prediction::create([
            'match_id' => $past->id,
            'market' => 'gg',
            'pick' => 'Yes',
            'probability' => 0.8,
            'is_top10' => true,
            'is_ai5' => true,
        ]);
        Result::create([
            'match_id' => $past->id,
            'home_score' => 2,
            'away_score' => 1,
            'actual_outcome' => ['win_draw_loss' => 'Home', 'gg' => 'Yes', 'over_2_5' => 'Over'],
            'settled_at' => now(),
        ]);

        $expert = Expert::create(['name' => 'Pundit Pete', 'bio' => 'Knows ball.']);
        ExpertPick::create([
            'expert_id' => $expert->id,
            'match_id' => $match->id,
            'market' => 'gg',
            'pick' => 'Yes',
            'rationale' => 'Both attack well.',
            'confidence' => 0.8,
        ]);

        return $match;
    }

    public static function guestRoutes(): array
    {
        return [
            ['/'],
            ['/how-ai-works'],
            ['/track-record'],
            ['/subscribe'],
            ['/top-picks'],
            ['/expert-picks'],
            ['/expert-leaderboard'],
            ['/login'],
            ['/register'],
            ['/sitemap.xml'],
            ['/robots.txt'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('guestRoutes')]
    public function test_guest_routes_do_not_error(string $uri): void
    {
        $this->seedDomain();

        $response = $this->get($uri);

        $this->assertLessThan(
            500,
            $response->getStatusCode(),
            "GET {$uri} as guest returned {$response->getStatusCode()}"
        );
    }

    public function test_match_detail_page_renders(): void
    {
        $match = $this->seedDomain();

        $response = $this->get("/matches/{$match->id}");
        $this->assertLessThan(500, $response->getStatusCode(), 'match detail errored');
    }

    public function test_authenticated_user_routes_do_not_error(): void
    {
        $this->seedDomain();
        $user = User::factory()->create(['role' => 'free']);

        foreach (['/account', '/expert/submit', '/account/telegram/connect'] as $uri) {
            $response = $this->actingAs($user)->get($uri);
            $this->assertLessThan(
                500,
                $response->getStatusCode(),
                "GET {$uri} as free user returned {$response->getStatusCode()}"
            );
        }
    }

    public function test_subscriber_routes_do_not_error(): void
    {
        $match = $this->seedDomain();
        $user = User::factory()->subscriber()->create();

        foreach (['/top-picks', '/expert-picks', "/matches/{$match->id}", '/track-record'] as $uri) {
            $response = $this->actingAs($user)->get($uri);
            $this->assertLessThan(
                500,
                $response->getStatusCode(),
                "GET {$uri} as subscriber returned {$response->getStatusCode()}"
            );
        }
    }

    public function test_admin_routes_do_not_error(): void
    {
        $match = $this->seedDomain();
        $admin = User::factory()->create(['role' => 'admin']);

        $uris = [
            '/admin/dashboard',
            '/admin/analytics',
            '/admin/settings',
            '/admin/matches',
            '/admin/matches/create',
            "/admin/matches/{$match->id}/edit",
            "/admin/matches/{$match->id}/settle",
            '/admin/predictions',
            '/admin/users',
            '/admin/experts',
            '/admin/ads',
        ];

        foreach ($uris as $uri) {
            $response = $this->actingAs($admin)->get($uri);
            $this->assertLessThan(
                500,
                $response->getStatusCode(),
                "GET {$uri} as admin returned {$response->getStatusCode()}"
            );
        }
    }
}
