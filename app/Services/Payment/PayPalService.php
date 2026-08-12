<?php

namespace App\Services\Payment;

use App\Models\User;
use App\Models\Subscription;
use Illuminate\Support\Facades\Http;

class PayPalService
{
    protected string $clientId;
    protected string $secret;
    protected string $baseUrl = 'https://api-m.sandbox.paypal.com';

    public function __construct()
    {
        $this->clientId = config('services.paypal.client_id') ?? env('PAYPAL_CLIENT_ID', 'PAYPAL_MOCK_CLIENT_ID');
        $this->secret = config('services.paypal.secret') ?? env('PAYPAL_SECRET', 'PAYPAL_MOCK_SECRET');
    }

    /**
     * Create subscription link for PayPal
     */
    public function createSubscriptionLink(User $user, string $planId = 'P-MOCK_PLAN_PRO'): string
    {
        if (str_contains($this->clientId, 'MOCK')) {
            return route('subscription.callback', [
                'gateway' => 'paypal',
                'status' => 'APPROVED',
                'subscription_id' => 'I-PP' . strtoupper(uniqid()),
                'user_id' => $user->id,
            ]);
        }

        $token = $this->getAccessToken();

        $response = Http::withToken($token)->post("{$this->baseUrl}/v1/billing/subscriptions", [
            'plan_id' => $planId,
            'subscriber' => [
                'name' => ['given_name' => $user->name],
                'email_address' => $user->email,
            ],
            'application_context' => [
                'return_url' => route('subscription.callback', ['gateway' => 'paypal']),
                'cancel_url' => route('subscription.pricing'),
            ],
        ]);

        if ($response->successful()) {
            $links = $response->json()['links'] ?? [];
            foreach ($links as $link) {
                if (($link['rel'] ?? '') === 'approve') {
                    return $link['href'];
                }
            }
        }

        return route('subscription.pricing');
    }

    protected function getAccessToken(): string
    {
        $response = Http::withBasicAuth($this->clientId, $this->secret)
            ->asForm()
            ->post("{$this->baseUrl}/v1/oauth2/token", [
                'grant_type' => 'client_credentials',
            ]);

        return $response->json()['access_token'] ?? '';
    }

    public function handleWebhook(array $payload): void
    {
        $eventType = $payload['event_type'] ?? '';
        $resource = $payload['resource'] ?? [];
        $email = $resource['subscriber']['email_address'] ?? null;

        if (!$email) {
            return;
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return;
        }

        if ($eventType === 'BILLING.SUBSCRIPTION.ACTIVATED' || $eventType === 'PAYMENT.SALE.COMPLETED') {
            Subscription::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'gateway' => 'paypal',
                    'gateway_subscription_id' => $resource['id'] ?? 'I-PP' . time(),
                    'status' => 'active',
                    'plan' => 'monthly_pro',
                    'renews_at' => now()->addMonth(),
                    'grace_period_ends_at' => null,
                ]
            );
            $user->update(['role' => 'subscriber']);
        } elseif ($eventType === 'BILLING.SUBSCRIPTION.PAYMENT.FAILED') {
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
