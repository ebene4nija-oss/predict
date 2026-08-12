<?php

namespace App\Services\Payment;

use App\Models\User;
use App\Models\Subscription;
use Illuminate\Support\Facades\Http;

class FlutterwaveService
{
    protected string $secretKey;
    protected string $baseUrl = 'https://api.flutterwave.com/v3';

    public function __construct()
    {
        $this->secretKey = config('services.flutterwave.secret_key') ?? env('FLUTTERWAVE_SECRET_KEY', 'FLWSECK_TEST_mock_key');
    }

    /**
     * Initialize subscription payment link for a user
     */
    public function createSubscriptionLink(User $user, string $planCode = 'monthly_pro', float $amount = 5000): string
    {
        // Mock payment link for test mode if secret key is default
        if (str_contains($this->secretKey, 'mock')) {
            return route('subscription.callback', [
                'gateway' => 'flutterwave',
                'status' => 'successful',
                'tx_ref' => 'flw_' . uniqid(),
                'user_id' => $user->id,
            ]);
        }

        $response = Http::withToken($this->secretKey)->post("{$this->baseUrl}/payments", [
            'tx_ref' => 'flw_sub_' . $user->id . '_' . time(),
            'amount' => $amount,
            'currency' => 'NGN',
            'redirect_url' => route('subscription.callback', ['gateway' => 'flutterwave']),
            'payment_options' => 'card,banktransfer,account',
            'customer' => [
                'email' => $user->email,
                'name' => $user->name,
            ],
            'customizations' => [
                'title' => 'PROPHET AI Monthly Subscription',
                'description' => 'Full access to AI Top 5, Top 10 lists & Expert Picks',
            ],
        ]);

        if ($response->successful()) {
            return $response->json()['data']['link'] ?? route('subscription.pricing');
        }

        return route('subscription.pricing');
    }

    /**
     * Process webhook or payment status change
     */
    public function handleWebhook(array $payload): void
    {
        $event = $payload['event'] ?? '';
        $data = $payload['data'] ?? [];
        $email = $data['customer']['email'] ?? null;

        if (!$email) {
            return;
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return;
        }

        if ($event === 'charge.completed' && ($data['status'] ?? '') === 'successful') {
            Subscription::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'gateway' => 'flutterwave',
                    'gateway_subscription_id' => $data['id'] ?? 'flw_' . time(),
                    'status' => 'active',
                    'plan' => 'monthly_pro',
                    'renews_at' => now()->addMonth(),
                    'grace_period_ends_at' => null,
                ]
            );
            $user->update(['role' => 'subscriber']);
        } elseif ($event === 'invoice.payment_failed') {
            // Failed payment -> mark past_due and set 7-day grace period
            $sub = Subscription::where('user_id', $user->id)->first();
            if ($sub) {
                $sub->update([
                    'status' => 'past_due',
                    'grace_period_ends_at' => now()->addDays(7),
                ]);
            }
        }
    }
}
