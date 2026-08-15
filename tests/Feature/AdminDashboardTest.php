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
            'home_advantage' => 1.15,
            'default_league_average' => 1.35,
            'fixture_provider' => 'football_data',
            'paypal_mode' => 'sandbox',
        ];

        $postResponse = $this->actingAs($admin)->post(route('admin.settings.update'), $postData);
        $postResponse->assertRedirect(route('admin.settings'));

        $this->assertEquals('test_gemini_key_123', Setting::get('gemini_api_key'));
        $this->assertEquals('test_claude_key_456', Setting::get('claude_api_key'));
    }

    public function test_credentials_are_encrypted_at_rest_and_never_rendered(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Setting::set('claude_api_key', 'sk-ant-super-secret');

        // Stored ciphertext, not the key itself.
        $stored = Setting::where('key', 'claude_api_key')->value('value');
        $this->assertNotSame('sk-ant-super-secret', $stored);
        $this->assertSame('sk-ant-super-secret', Setting::get('claude_api_key'));

        // The settings screen reports "configured" without echoing the value.
        $this->actingAs($admin)->get(route('admin.settings'))
            ->assertOk()
            ->assertDontSee('sk-ant-super-secret');
    }

    public function test_blank_credential_field_does_not_erase_the_stored_key(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Setting::set('claude_api_key', 'sk-ant-keep-me');

        $this->actingAs($admin)->post(route('admin.settings.update'), [
            'claude_api_key' => '',
            'prediction_provider' => 'claude',
            'min_confidence_threshold' => 0.60,
            'home_advantage' => 1.15,
            'default_league_average' => 1.35,
            'fixture_provider' => 'football_data',
            'paypal_mode' => 'sandbox',
        ])->assertRedirect(route('admin.settings'));

        $this->assertSame('sk-ant-keep-me', Setting::get('claude_api_key'));
    }

    public function test_non_admin_cannot_access_admin_portal(): void
    {
        $user = User::factory()->create(['role' => 'free']);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));
        $response->assertRedirect(route('home'));
    }
}
