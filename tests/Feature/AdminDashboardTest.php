<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_dashboard_and_update_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertStatus(200);

        $response = $this->actingAs($admin)->get(route('admin.settings'));
        $response->assertStatus(200);

        $postData = [
            'gemini_api_key' => 'test_gemini_key_123',
            'claude_api_key' => 'test_claude_key_456',
            'prediction_provider' => 'claude',
            'min_confidence_threshold' => 0.60,
            'poisson_home_weight' => 1.4,
            'poisson_away_weight' => 1.2,
        ];

        $postResponse = $this->actingAs($admin)->post(route('admin.settings.update'), $postData);
        $postResponse->assertRedirect(route('admin.settings'));

        $this->assertEquals('test_gemini_key_123', Setting::get('gemini_api_key'));
        $this->assertEquals('test_claude_key_456', Setting::get('claude_api_key'));
    }

    public function test_non_admin_cannot_access_admin_portal(): void
    {
        $user = User::factory()->create(['role' => 'free']);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));
        $response->assertRedirect(route('home'));
    }
}
