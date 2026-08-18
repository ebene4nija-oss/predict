<?php

namespace App\Services\Payment;

use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPalService
{
    public function __construct(protected SubscriptionManager $subscriptions) {}

    /**
     * Read lazily rather than in the constructor, so a key saved in the admin
     * dashboard takes effect on the next call instead of the next deploy.
     */
    protected function clientId(): string
    {
        return Setting::credential('paypal_client_id', 'services.paypal.client_id');
    }

    protected function secret(): string
    {
        return Setting::credential('paypal_secret', 'services.paypal.secret');
    }

    protected function webhookId(): string
    {
        return Setting::credential('paypal_webhook_id', 'services.paypal.webhook_id');
    }

    protected function planId(): string
    {
        return Setting::credential('paypal_plan_id', 'services.paypal.plan_id');
    }

    protected function mode(): string
    {
        return Setting::credential('paypal_mode', 'services.paypal.mode') ?: 'sandbox';
    }

    protected function baseUrl(): string
    {
        return $this->mode() === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    /**
     * Mock mode is a local-only convenience — see FlutterwaveService::isMockMode().
     */
    public function isMockMode(): bool
    {
        if (! app()->environment('local', 'testing')) {
            return false;
        }

        return $this->clientId() === '' || str_contains(strtoupper($this->clientId()), 'MOCK');
    }

    public function email(): string
    {
        return Setting::credential('paypal_email', 'services.paypal.email');
    }

    public function isConfigured(): bool
    {
        return filled($this->email()) || (filled($this->clientId()) && filled($this->secret()));
    }

    /**
     * Create a PayPal subscription approval link. Null when unavailable.
     */
    public function createSubscriptionLink(User $user, ?float $amount = null, string $currency = 'USD'): ?string
    {
        if ($this->isMockMode()) {
            return route('subscription.callback', [
                'gateway' => 'paypal',
                'subscription_id' => 'I-PP' . strtoupper(uniqid()),
            ]);
        }

        $email = $this->email();

        // Direct PayPal Email / PayPal.me Mode (No developer API keys required)
        if (filled($email)) {
            $pricing = app(PricingResolver::class)->forUser($user);
            $amount = $amount ?? (float) ($pricing['amount'] ?? 4.99);
            $currency = $currency ?: ($pricing['currency'] ?? 'USD');

            // If it's a paypal.me link
            if (str_contains(strtolower($email), 'paypal.me/')) {
                $cleanHandle = trim(str_ireplace(['https://', 'http://', 'www.', 'paypal.me/'], '', $email), '/');

                return "https://paypal.me/{$cleanHandle}/" . number_format($amount, 2, '.', '') . "{$currency}";
            }

            // Standard PayPal Direct Subscription / Checkout Link
            $returnUrl = route('subscription.callback', [
                'gateway' => 'paypal',
                'subscription_id' => 'PP-SUB-' . strtoupper(substr(md5($user->id . time() . uniqid()), 0, 12)),
            ]);
            $cancelUrl = route('subscription.pricing');

            $params = http_build_query([
                'cmd' => '_xclick-subscriptions',
                'business' => $email,
                'item_name' => 'GUARANTEED CORRECT Monthly PRO - ' . $user->email,
                'item_number' => 'PRO_MONTHLY_' . $user->id,
                'currency_code' => $currency,
                'a3' => number_format($amount, 2, '.', ''),
                'p3' => 1,
                't3' => 'M',
                'src' => 1,
                'sra' => 1,
                'no_shipping' => 1,
                'no_note' => 1,
                'custom' => (string) $user->id,
                'return' => $returnUrl,
                'cancel_return' => $cancelUrl,
            ]);

            return "https://www.paypal.com/cgi-bin/webscr?{$params}";
        }

        if ($this->clientId() === '' || $this->secret() === '' || $this->planId() === '') {
            Log::error('PayPal email or API credentials/plan id are not configured.');

            return null;
        }

        $token = $this->getAccessToken();

        if ($token === '') {
            return null;
        }

        $response = Http::withToken($token)->post("{$this->baseUrl()}/v1/billing/subscriptions", [
            'plan_id' => $this->planId(),
            'subscriber' => [
                'name' => ['given_name' => $user->name],
                'email_address' => $user->email,
            ],
            'application_context' => [
                'return_url' => route('subscription.callback', ['gateway' => 'paypal']),
                'cancel_url' => route('subscription.pricing'),
            ],
        ]);

        if (! $response->successful()) {
            Log::error('PayPal subscription creation failed', [
                'user_id' => $user->id,
                'status' => $response->status(),
            ]);

            return null;
        }

        foreach ($response->json('links') ?? [] as $link) {
            if (($link['rel'] ?? '') === 'approve') {
                return $link['href'];
            }
        }

        return null;
    }

    protected function getAccessToken(): string
    {
        $response = Http::withBasicAuth($this->clientId(), $this->secret())
            ->asForm()
            ->post("{$this->baseUrl()}/v1/oauth2/token", ['grant_type' => 'client_credentials']);

        if (! $response->successful()) {
            Log::error('PayPal access token request failed', ['status' => $response->status()]);

            return '';
        }

        return (string) $response->json('access_token', '');
    }

    /**
     * Confirm a subscription is genuinely active and belongs to this user.
     */
    public function verifySubscription(string $subscriptionId, User $user): ?string
    {
        if ($subscriptionId === '') {
            return null;
        }

        if ($this->isMockMode()) {
            return $subscriptionId;
        }

        // Direct PayPal Email mode
        if (filled($this->email()) && str_starts_with($subscriptionId, 'PP-SUB-')) {
            Log::info('Direct PayPal Email subscription activated on callback', [
                'user_id' => $user->id,
                'subscription_id' => $subscriptionId,
            ]);

            return $subscriptionId;
        }

        $token = $this->getAccessToken();

        if ($token === '') {
            return null;
        }

        $response = Http::withToken($token)
            ->get("{$this->baseUrl()}/v1/billing/subscriptions/{$subscriptionId}");

        if (! $response->successful()) {
            Log::warning('PayPal subscription lookup failed', [
                'subscription_id' => $subscriptionId,
                'status' => $response->status(),
            ]);

            return null;
        }

        $data = $response->json();
        $status = (string) ($data['status'] ?? '');

        // Only ACTIVE means PayPal has actually collected the first payment.
        // APPROVED means the buyer agreed but billing has not run yet, so it is
        // not proof of payment; those users are activated moments later by the
        // BILLING.SUBSCRIPTION.ACTIVATED webhook instead.
        $isActive = $status === 'ACTIVE';
        $ownedByUser = strcasecmp((string) ($data['subscriber']['email_address'] ?? ''), $user->email) === 0;

        if (! $isActive || ! $ownedByUser) {
            Log::warning('PayPal subscription rejected', [
                'subscription_id' => $subscriptionId,
                'user_id' => $user->id,
                'status' => $status,
                'email_match' => $ownedByUser,
            ]);

            return null;
        }

        return (string) ($data['id'] ?? $subscriptionId);
    }

    /**
     * Ask PayPal to verify the signature on an incoming webhook.
     */
    public function verifyWebhookSignature(Request $request): bool
    {
        if ($this->webhookId() === '') {
            Log::error('PayPal webhook id is not configured; rejecting webhook.');

            return false;
        }

        $required = [
            'transmission_id' => 'paypal-transmission-id',
            'transmission_time' => 'paypal-transmission-time',
            'transmission_sig' => 'paypal-transmission-sig',
            'cert_url' => 'paypal-cert-url',
            'auth_algo' => 'paypal-auth-algo',
        ];

        $headers = [];
        foreach ($required as $field => $header) {
            $value = $request->header($header);
            if (! $value) {
                Log::warning('PayPal webhook missing signature header', ['header' => $header]);

                return false;
            }
            $headers[$field] = $value;
        }

        $token = $this->getAccessToken();

        if ($token === '') {
            return false;
        }

        $response = Http::withToken($token)
            ->post("{$this->baseUrl()}/v1/notifications/verify-webhook-signature", $headers + [
                'webhook_id' => $this->webhookId(),
                'webhook_event' => $request->all(),
            ]);

        return $response->successful()
            && $response->json('verification_status') === 'SUCCESS';
    }

    /**
     * Process a verified webhook payload.
     */
    public function handleWebhook(array $payload): void
    {
        $eventType = $payload['event_type'] ?? '';
        $resource = $payload['resource'] ?? [];

        $reference = $this->subscriptionReference($resource);
        $user = $this->resolveUser($resource, $reference);

        if (! $user) {
            Log::warning('PayPal webhook could not be matched to a user', [
                'event_type' => $eventType,
                'reference' => $reference,
            ]);

            return;
        }

        if (in_array($eventType, ['BILLING.SUBSCRIPTION.ACTIVATED', 'PAYMENT.SALE.COMPLETED'], true)) {
            $this->subscriptions->activate($user, 'paypal', $reference ?? 'I-PP' . now()->timestamp);
        } elseif ($eventType === 'BILLING.SUBSCRIPTION.PAYMENT.FAILED') {
            $this->subscriptions->markPastDue($user);
        } elseif (in_array($eventType, ['BILLING.SUBSCRIPTION.CANCELLED', 'BILLING.SUBSCRIPTION.EXPIRED'], true)) {
            $this->subscriptions->cancel($user);
        }
    }

    /**
     * The subscription this event refers to.
     *
     * Subscription events carry the subscription id as `id`. Sale events — the
     * recurring renewal payments — put the sale id there instead and name the
     * subscription `billing_agreement_id`.
     */
    protected function subscriptionReference(array $resource): ?string
    {
        $reference = $resource['billing_agreement_id'] ?? $resource['id'] ?? null;

        return $reference === null ? null : (string) $reference;
    }

    /**
     * Match the event to a local user.
     *
     * Only subscription events embed the subscriber's email address; sale
     * events do not, so those fall back to the subscription reference stored at
     * activation. Without that fallback every recurring renewal was silently
     * dropped for want of an email.
     */
    protected function resolveUser(array $resource, ?string $reference): ?User
    {
        $email = $resource['subscriber']['email_address'] ?? null;

        if ($email && $user = User::where('email', $email)->first()) {
            return $user;
        }

        if ($reference === null) {
            return null;
        }

        return Subscription::where('gateway', 'paypal')
            ->where('gateway_subscription_id', $reference)
            ->first()?->user;
    }
}
