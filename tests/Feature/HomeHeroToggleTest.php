<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeHeroToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_shows_hero_and_trust_stats_by_default(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Guaranteed Correct Score Tips', false)
            ->assertSee('Overall AI Hit Rate', false);
    }

    public function test_admin_can_disable_homepage_trust_stats_strip(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.settings.update'), [
                'prediction_provider' => 'gemini',
                'fixture_provider' => 'football_data',
                'min_confidence_threshold' => 0.55,
                'home_advantage' => 1.3,
                'default_league_average' => 2.5,
                'paypal_mode' => 'sandbox',
                'homepage_toggles_present' => '1',
                'home_hero_stats_enabled' => '0',
                'home_hero_section_enabled' => '1',
            ])
            ->assertRedirect(route('admin.settings'));

        $this->assertEquals('0', Setting::get('home_hero_stats_enabled'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Guaranteed Correct Score Tips', false)
            ->assertDontSee('Overall AI Hit Rate');
    }

    public function test_admin_can_disable_entire_homepage_hero_section(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.settings.update'), [
                'prediction_provider' => 'gemini',
                'fixture_provider' => 'football_data',
                'min_confidence_threshold' => 0.55,
                'home_advantage' => 1.3,
                'default_league_average' => 2.5,
                'paypal_mode' => 'sandbox',
                'homepage_toggles_present' => '1',
                'home_hero_stats_enabled' => '0',
                'home_hero_section_enabled' => '0',
            ])
            ->assertRedirect(route('admin.settings'));

        $this->assertEquals('0', Setting::get('home_hero_section_enabled'));

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Poisson Probability Engine 2.4')
            ->assertDontSee('Overall AI Hit Rate');
    }
}
