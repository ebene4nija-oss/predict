<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Payment\PricingResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Checkout used to default to Flutterwave in naira for everyone, so a payer
 * outside Africa was quoted a currency their card could not settle.
 */
class CheckoutRoutingTest extends TestCase
{
    use RefreshDatabase;

    protected function resolveFor(array $headers = [], array $input = []): array
    {
        $request = Request::create('/subscribe', 'GET', $input);

        foreach ($headers as $key => $value) {
            $request->headers->set($key, $value);
        }

        return (new PricingResolver)->resolve($request);
    }

    public function test_nigerian_visitor_gets_flutterwave_in_naira(): void
    {
        $resolved = $this->resolveFor(['CF-IPCountry' => 'NG']);

        $this->assertSame('flutterwave', $resolved['gateway']);
        $this->assertSame('NGN', $resolved['currency']);
        $this->assertSame(5000.0, $resolved['amount']);
    }

    public function test_visitor_outside_the_flutterwave_markets_gets_paypal_in_dollars(): void
    {
        $resolved = $this->resolveFor(['CF-IPCountry' => 'GB']);

        $this->assertSame('paypal', $resolved['gateway']);
        $this->assertSame('USD', $resolved['currency']);
    }

    public function test_other_african_markets_stay_on_flutterwave(): void
    {
        $resolved = $this->resolveFor(['CF-IPCountry' => 'KE']);

        $this->assertSame('flutterwave', $resolved['gateway']);
        $this->assertSame('USD', $resolved['currency']);
    }

    public function test_an_explicit_choice_overrides_geo_detection(): void
    {
        // Nigerian card, browsing from London.
        $resolved = $this->resolveFor(['CF-IPCountry' => 'GB'], ['country' => 'NG']);

        $this->assertSame('flutterwave', $resolved['gateway']);
        $this->assertSame('NGN', $resolved['currency']);
    }

    public function test_unplaceable_visitors_fall_back_to_the_default_market(): void
    {
        // Cloudflare sends XX when it cannot place the client.
        $resolved = $this->resolveFor(['CF-IPCountry' => 'XX']);

        $this->assertSame(config('pricing.default_country'), $resolved['country']);
        $this->assertFalse($resolved['detected']);
    }

    public function test_pricing_page_shows_the_local_price(): void
    {
        $this->withServerVariables(['HTTP_CF_IPCOUNTRY' => 'GB'])
            ->get('/subscribe')
            ->assertOk()
            ->assertSee('USD')
            ->assertDontSee('₦5,000');
    }

    public function test_checkout_follows_the_detected_market_without_a_gateway_field(): void
    {
        $user = User::factory()->create(['role' => 'free']);

        // Mock mode returns a callback URL naming the gateway that was used.
        $response = $this->actingAs($user)
            ->withServerVariables(['HTTP_CF_IPCOUNTRY' => 'GB'])
            ->post('/checkout');

        $response->assertRedirectContains('gateway=paypal');
    }

    public function test_checkout_still_honours_an_explicit_gateway_choice(): void
    {
        $user = User::factory()->create(['role' => 'free']);

        $response = $this->actingAs($user)
            ->withServerVariables(['HTTP_CF_IPCOUNTRY' => 'GB'])
            ->post('/checkout', ['gateway' => 'flutterwave', 'country' => 'NG']);

        $response->assertRedirectContains('gateway=flutterwave');
    }

    public function test_an_unpriced_currency_is_refused_rather_than_charged(): void
    {
        config(['pricing.countries.NG' => ['gateway' => 'flutterwave', 'currency' => 'XOF']]);

        $user = User::factory()->create(['role' => 'free']);

        $this->actingAs($user)
            ->withServerVariables(['HTTP_CF_IPCOUNTRY' => 'NG'])
            ->post('/checkout')
            ->assertRedirect(route('subscription.pricing'))
            ->assertSessionHas('error');
    }

    public function test_admin_custom_prices_override_default_config(): void
    {
        \App\Models\Setting::set('price_ngn', 7500);
        \App\Models\Setting::set('price_usd', 9.99);

        $ngResolved = $this->resolveFor(['CF-IPCountry' => 'NG']);
        $this->assertSame(7500.0, $ngResolved['amount']);
        $this->assertSame('₦7,500', $ngResolved['formatted']);

        $usResolved = $this->resolveFor(['CF-IPCountry' => 'US']);
        $this->assertSame(9.99, $usResolved['amount']);
        $this->assertSame('$9.99', $usResolved['formatted']);
    }

    public function test_admin_settings_can_update_subscription_prices(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.settings.update'), [
            'fixture_provider' => 'sample',
            'min_confidence_threshold' => 0.55,
            'home_advantage' => 1.15,
            'default_league_average' => 1.35,
            'prediction_provider' => 'claude',
            'paypal_mode' => 'sandbox',
            'price_ngn' => 6000,
            'price_usd' => 7.50,
            'pricing_default_country' => 'NG',
        ]);

        $response->assertRedirect(route('admin.settings'));
        $this->assertSame(6000.0, (new PricingResolver)->amountFor('NGN'));
        $this->assertSame(7.50, (new PricingResolver)->amountFor('USD'));
    }
}

