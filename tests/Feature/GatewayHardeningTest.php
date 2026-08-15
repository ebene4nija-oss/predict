<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use App\Services\Payment\FlutterwaveService;
use App\Services\Payment\PayPalService;
use App\Support\MarketOutcome;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Covers the four hardening fixes applied after the payment review.
 */
class GatewayHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function clearGatewayCredentials(): void
    {
        config([
            'services.flutterwave.secret_key' => '',
            'services.paypal.client_id' => '',
        ]);
    }

    public function test_mock_mode_is_available_on_a_developer_machine(): void
    {
        $this->clearGatewayCredentials();
        app()->detectEnvironment(fn () => 'local');

        $this->assertTrue(app(FlutterwaveService::class)->isMockMode());
        $this->assertTrue(app(PayPalService::class)->isMockMode());
    }

    /**
     * The bug: mock mode only checked for production, so an unconfigured
     * staging or demo deploy handed out free subscriptions.
     */
    public function test_mock_mode_never_engages_outside_local_and_testing(): void
    {
        $this->clearGatewayCredentials();

        foreach (['staging', 'demo', 'production'] as $environment) {
            app()->detectEnvironment(fn () => $environment);

            $this->assertFalse(
                app(FlutterwaveService::class)->isMockMode(),
                "Flutterwave must not mock in {$environment}"
            );
            $this->assertFalse(
                app(PayPalService::class)->isMockMode(),
                "PayPal must not mock in {$environment}"
            );
        }
    }

    public function test_an_unconfigured_gateway_refuses_to_create_a_checkout_link(): void
    {
        $this->clearGatewayCredentials();
        app()->detectEnvironment(fn () => 'staging');

        $user = User::factory()->create();

        $this->assertNull(app(FlutterwaveService::class)->createSubscriptionLink($user));
        $this->assertNull(app(PayPalService::class)->createSubscriptionLink($user));
    }

    /**
     * APPROVED means the buyer agreed but PayPal has not billed them yet.
     */
    public function test_an_approved_but_unbilled_paypal_subscription_is_not_accepted(): void
    {
        config([
            'services.paypal.client_id' => 'live-client-id',
            'services.paypal.secret' => 'live-secret',
        ]);

        $user = User::factory()->create(['role' => 'free']);

        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'token']),
            '*/v1/billing/subscriptions/*' => Http::response([
                'id' => 'I-APPROVED',
                'status' => 'APPROVED',
                'subscriber' => ['email_address' => $user->email],
            ]),
        ]);

        $this->assertNull(
            app(PayPalService::class)->verifySubscription('I-APPROVED', $user)
        );

        $this->actingAs($user)
            ->get('/subscription/callback?gateway=paypal&subscription_id=I-APPROVED')
            ->assertRedirect(route('subscription.pricing'));

        $this->assertSame('free', $user->fresh()->role);
    }

    public function test_an_active_paypal_subscription_is_accepted(): void
    {
        config([
            'services.paypal.client_id' => 'live-client-id',
            'services.paypal.secret' => 'live-secret',
        ]);

        $user = User::factory()->create(['role' => 'free']);

        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'token']),
            '*/v1/billing/subscriptions/*' => Http::response([
                'id' => 'I-ACTIVE',
                'status' => 'ACTIVE',
                'subscriber' => ['email_address' => $user->email],
            ]),
        ]);

        $this->assertSame(
            'I-ACTIVE',
            app(PayPalService::class)->verifySubscription('I-ACTIVE', $user)
        );
    }

    /**
     * The bug: PAYMENT.SALE.COMPLETED carries no subscriber email, so the
     * handler bailed out and recurring renewals were silently dropped.
     */
    public function test_a_recurring_sale_renews_the_subscription_without_an_email(): void
    {
        $user = User::factory()->create(['role' => 'free']);

        Subscription::create([
            'user_id' => $user->id,
            'gateway' => 'paypal',
            'gateway_subscription_id' => 'I-RENEWING',
            'status' => 'past_due',
            'plan' => 'monthly_pro',
            'renews_at' => now()->subDay(),
            'grace_period_ends_at' => now()->addDays(3),
        ]);

        app(PayPalService::class)->handleWebhook([
            'event_type' => 'PAYMENT.SALE.COMPLETED',
            'resource' => [
                'id' => 'SALE-9001',
                'billing_agreement_id' => 'I-RENEWING',
                'amount' => ['total' => '30.00', 'currency' => 'USD'],
            ],
        ]);

        $this->assertSame('subscriber', $user->fresh()->role);
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'status' => 'active',
            'gateway_subscription_id' => 'I-RENEWING',
        ]);
    }

    public function test_a_sale_for_an_unknown_subscription_changes_nothing(): void
    {
        $user = User::factory()->create(['role' => 'free']);

        app(PayPalService::class)->handleWebhook([
            'event_type' => 'PAYMENT.SALE.COMPLETED',
            'resource' => [
                'id' => 'SALE-UNKNOWN',
                'billing_agreement_id' => 'I-DOES-NOT-EXIST',
            ],
        ]);

        $this->assertSame('free', $user->fresh()->role);
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_unscoreable_picks_are_excluded_rather_than_counted_as_losses(): void
    {
        $this->assertTrue(MarketOutcome::isGradeable('win_draw_loss', 'Home Win'));
        $this->assertTrue(MarketOutcome::isGradeable('gg', 'yes'));

        $this->assertFalse(MarketOutcome::isGradeable('win_draw_loss', 'Home or Draw'));
        $this->assertFalse(MarketOutcome::isGradeable('gg', null));
        $this->assertFalse(MarketOutcome::isGradeable('gg', ''));
        $this->assertFalse(MarketOutcome::isGradeable('unknown_market', 'Yes'));
    }
}
