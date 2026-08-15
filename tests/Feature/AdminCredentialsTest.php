<?php

namespace Tests\Feature;

use App\Contracts\FixtureProvider;
use App\Models\Setting;
use App\Models\User;
use App\Services\Fixtures\FootballDataProvider;
use App\Services\Fixtures\SampleFixtureProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminCredentialsTest extends TestCase
{
    use RefreshDatabase;

    /** The full set of credentials the app consumes. */
    protected const CREDENTIALS = [
        'claude_api_key',
        'gemini_api_key',
        'football_data_token',
        'telegram_bot_token',
        'telegram_webhook_secret',
        'flutterwave_secret_key',
        'flutterwave_public_key',
        'flutterwave_webhook_hash',
        'paypal_client_id',
        'paypal_secret',
        'paypal_webhook_id',
    ];

    protected function baseSettings(): array
    {
        return [
            'prediction_provider' => 'claude',
            'min_confidence_threshold' => 0.60,
            'home_advantage' => 1.15,
            'default_league_average' => 1.35,
            'fixture_provider' => 'football_data',
            'paypal_mode' => 'sandbox',
        ];
    }

    public function test_every_credential_can_be_saved_from_the_admin_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $payload = $this->baseSettings();

        foreach (self::CREDENTIALS as $key) {
            $payload[$key] = "value-for-{$key}";
        }

        $this->actingAs($admin)
            ->post(route('admin.settings.update'), $payload)
            ->assertRedirect(route('admin.settings'))
            ->assertSessionHasNoErrors();

        foreach (self::CREDENTIALS as $key) {
            $this->assertSame("value-for-{$key}", Setting::get($key), "{$key} did not persist");
        }
    }

    public function test_every_credential_is_encrypted_at_rest(): void
    {
        foreach (self::CREDENTIALS as $key) {
            Setting::set($key, "plaintext-{$key}");

            $this->assertNotSame(
                "plaintext-{$key}",
                Setting::where('key', $key)->value('value'),
                "{$key} is stored in plaintext"
            );
        }
    }

    public function test_the_settings_screen_never_echoes_a_credential(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (self::CREDENTIALS as $key) {
            Setting::set($key, "supersecret-{$key}");
        }

        $response = $this->actingAs($admin)->get(route('admin.settings'))->assertOk();

        foreach (self::CREDENTIALS as $key) {
            $response->assertDontSee("supersecret-{$key}");
        }
    }

    /**
     * The point of moving keys into the dashboard: saving one must change
     * behaviour without a redeploy or an .env edit.
     */
    public function test_a_dashboard_key_is_used_by_the_gateway_without_touching_env(): void
    {
        config(['services.flutterwave.webhook_hash' => '']);
        Setting::set('flutterwave_webhook_hash', 'hash-from-dashboard');

        $user = User::factory()->create(['role' => 'free']);

        $this->withHeader('verif-hash', 'hash-from-dashboard')
            ->postJson('/webhooks/payment', [
                'event' => 'charge.completed',
                'data' => [
                    'id' => 'flw_dash_1',
                    'status' => 'successful',
                    'amount' => 5000,
                    'currency' => 'NGN',
                    'customer' => ['email' => $user->email],
                ],
            ])->assertOk();

        $this->assertSame('subscriber', $user->fresh()->role);
    }

    public function test_a_wrong_signature_is_still_rejected_when_the_hash_comes_from_the_dashboard(): void
    {
        config(['services.flutterwave.webhook_hash' => '']);
        Setting::set('flutterwave_webhook_hash', 'hash-from-dashboard');

        $user = User::factory()->create(['role' => 'free']);

        $this->withHeader('verif-hash', 'not-the-right-hash')
            ->postJson('/webhooks/payment', [
                'event' => 'charge.completed',
                'data' => ['status' => 'successful', 'customer' => ['email' => $user->email]],
            ])->assertStatus(401);

        $this->assertSame('free', $user->fresh()->role);
    }

    public function test_the_dashboard_value_takes_precedence_over_the_env_fallback(): void
    {
        config(['services.football_data.token' => 'token-from-env']);

        $this->assertSame('token-from-env', Setting::credential('football_data_token', 'services.football_data.token'));

        Setting::set('football_data_token', 'token-from-dashboard');

        $this->assertSame('token-from-dashboard', Setting::credential('football_data_token', 'services.football_data.token'));
    }

    public function test_the_fixture_provider_can_be_switched_from_the_dashboard(): void
    {
        Setting::set('fixture_provider', 'sample');
        $this->assertInstanceOf(SampleFixtureProvider::class, app(FixtureProvider::class));

        Setting::set('fixture_provider', 'football_data');
        $this->assertInstanceOf(FootballDataProvider::class, app(FixtureProvider::class));
    }

    public function test_the_paypal_environment_toggle_switches_the_api_host(): void
    {
        Setting::set('paypal_client_id', 'live-client');
        Setting::set('paypal_secret', 'live-secret');
        Setting::set('paypal_webhook_id', 'live-webhook');
        Setting::set('paypal_mode', 'live');

        Http::fake(['*' => Http::response(['access_token' => 'tok'])]);

        // verifyWebhookSignature reaches for a token, which exercises baseUrl().
        app(\App\Services\Payment\PayPalService::class)->verifyWebhookSignature(
            tap(request(), fn ($r) => $r->headers->add([
                'paypal-transmission-id' => 'a',
                'paypal-transmission-time' => 'b',
                'paypal-transmission-sig' => 'c',
                'paypal-cert-url' => 'd',
                'paypal-auth-algo' => 'e',
            ]))
        );

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api-m.paypal.com'));
    }
}
