<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Force the gateways out of local mock mode so real verification runs.
     */
    protected function useLiveGatewayConfig(): void
    {
        config([
            'services.flutterwave.secret_key' => 'FLWSECK-live-test-key',
            'services.flutterwave.webhook_hash' => 'the-secret-hash',
            'services.paypal.client_id' => 'live-client-id',
            'services.paypal.secret' => 'live-secret',
            'services.paypal.webhook_id' => 'live-webhook-id',
        ]);
    }

    public function test_guest_cannot_activate_a_subscription_through_the_callback(): void
    {
        $victim = User::factory()->create(['role' => 'free']);

        $this->get('/subscription/callback?gateway=paypal&user_id=' . $victim->id)
            ->assertRedirect('/login');

        $this->assertSame('free', $victim->fresh()->role);
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_user_id_in_the_query_string_cannot_upgrade_another_account(): void
    {
        $this->useLiveGatewayConfig();
        Http::fake(); // any verification call fails closed

        $attacker = User::factory()->create(['role' => 'free']);
        $victim = User::factory()->create(['role' => 'free']);

        $this->actingAs($attacker)
            ->get('/subscription/callback?gateway=flutterwave&tx_ref=whatever&user_id=' . $victim->id)
            ->assertRedirect(route('subscription.pricing'));

        $this->assertSame('free', $attacker->fresh()->role);
        $this->assertSame('free', $victim->fresh()->role);
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_unverified_transaction_does_not_grant_access(): void
    {
        $this->useLiveGatewayConfig();

        $user = User::factory()->create(['role' => 'free']);

        Http::fake([
            '*/transactions/verify_by_reference*' => Http::response([
                'data' => ['status' => 'failed', 'amount' => 0],
            ]),
        ]);

        $this->actingAs($user)
            ->get('/subscription/callback?gateway=flutterwave&tx_ref=flw_unpaid')
            ->assertRedirect(route('subscription.pricing'));

        $this->assertSame('free', $user->fresh()->role);
    }

    public function test_transaction_paid_by_someone_else_does_not_grant_access(): void
    {
        $this->useLiveGatewayConfig();

        $user = User::factory()->create(['role' => 'free']);

        Http::fake([
            '*/transactions/verify_by_reference*' => Http::response([
                'data' => [
                    'id' => 'txn_1',
                    'status' => 'successful',
                    'amount' => 5000,
                    'currency' => 'NGN',
                    'customer' => ['email' => 'someone.else@example.com'],
                ],
            ]),
        ]);

        $this->actingAs($user)
            ->get('/subscription/callback?gateway=flutterwave&tx_ref=flw_other')
            ->assertRedirect(route('subscription.pricing'));

        $this->assertSame('free', $user->fresh()->role);
    }

    public function test_verified_transaction_activates_the_subscription(): void
    {
        $this->useLiveGatewayConfig();

        $user = User::factory()->create(['role' => 'free']);

        Http::fake([
            '*/transactions/verify_by_reference*' => Http::response([
                'data' => [
                    'id' => 'txn_success',
                    'status' => 'successful',
                    'amount' => 5000,
                    'currency' => 'NGN',
                    'customer' => ['email' => $user->email],
                ],
            ]),
        ]);

        $this->actingAs($user)
            ->get('/subscription/callback?gateway=flutterwave&tx_ref=flw_good')
            ->assertRedirect(route('top.picks'));

        $this->assertSame('subscriber', $user->fresh()->role);
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'status' => 'active',
            'gateway_subscription_id' => 'txn_success',
        ]);
    }

    public function test_payment_webhook_rejects_a_forged_payload(): void
    {
        $this->useLiveGatewayConfig();

        $victim = User::factory()->create(['role' => 'free']);

        $this->postJson('/webhooks/payment', [
            'event' => 'charge.completed',
            'data' => [
                'status' => 'successful',
                'customer' => ['email' => $victim->email],
            ],
        ])->assertStatus(401);

        $this->assertSame('free', $victim->fresh()->role);
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_a_signed_webhook_that_underpays_does_not_activate_a_subscription(): void
    {
        // A valid signature proves the payload came from the gateway, not that
        // it paid for a subscription — a ₦100 charge is still a ₦100 charge.
        $this->useLiveGatewayConfig();

        $user = User::factory()->create(['role' => 'free']);

        $this->withHeader('verif-hash', 'the-secret-hash')
            ->postJson('/webhooks/payment', [
                'event' => 'charge.completed',
                'data' => [
                    'id' => 'flw_hook_cheap',
                    'status' => 'successful',
                    'amount' => 100,
                    'currency' => 'NGN',
                    'customer' => ['email' => $user->email],
                ],
            ])->assertOk();

        $this->assertSame('free', $user->fresh()->role);
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_payment_webhook_accepts_a_correctly_signed_payload(): void
    {
        $this->useLiveGatewayConfig();

        $user = User::factory()->create(['role' => 'free']);

        $this->withHeader('verif-hash', 'the-secret-hash')
            ->postJson('/webhooks/payment', [
                'event' => 'charge.completed',
                'data' => [
                    'id' => 'flw_hook_1',
                    'status' => 'successful',
                    'amount' => 5000,
                    'currency' => 'NGN',
                    'customer' => ['email' => $user->email],
                ],
            ])->assertOk();

        $this->assertSame('subscriber', $user->fresh()->role);
    }

    public function test_payment_webhook_is_rejected_when_no_hash_is_configured(): void
    {
        config(['services.flutterwave.webhook_hash' => '']);

        $user = User::factory()->create(['role' => 'free']);

        $this->withHeader('verif-hash', 'anything')
            ->postJson('/webhooks/payment', [
                'event' => 'charge.completed',
                'data' => ['status' => 'successful', 'customer' => ['email' => $user->email]],
            ])->assertStatus(401);

        $this->assertSame('free', $user->fresh()->role);
    }

    public function test_cancelling_revokes_access(): void
    {
        $user = User::factory()->subscriber()->create();
        Subscription::create([
            'user_id' => $user->id,
            'gateway' => 'flutterwave',
            'status' => 'active',
            'plan' => 'monthly_pro',
            'renews_at' => now()->addMonth(),
        ]);

        $this->actingAs($user)->post('/cancel-subscription')->assertRedirect(route('account'));

        $user->refresh();
        $this->assertSame('free', $user->role);
        $this->assertFalse($user->isSubscriber());
    }

    public function test_telegram_webhook_rejects_updates_without_the_shared_secret(): void
    {
        config(['services.telegram.webhook_secret' => 'tg-secret']);

        $user = User::factory()->create(['telegram_link_token' => 'link-token-123']);

        $this->postJson('/webhooks/telegram', [
            'message' => [
                'chat' => ['id' => 999],
                'from' => ['first_name' => 'Mallory'],
                'text' => '/start link-token-123',
            ],
        ])->assertStatus(403);

        $this->assertNull($user->fresh()->telegram_chat_id);
    }
}
