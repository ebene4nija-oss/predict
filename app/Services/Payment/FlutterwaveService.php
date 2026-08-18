<?php

namespace App\Services\Payment;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FlutterwaveService
{
    protected string $baseUrl = 'https://api.flutterwave.com/v3';

    public const PLAN_AMOUNT = 5000.0;
    public const PLAN_CURRENCY = 'NGN';

    public function __construct(
        protected SubscriptionManager $subscriptions,
        protected PricingResolver $pricing,
    ) {}

    /**
     * Read lazily rather than in the constructor, so a key saved in the admin
     * dashboard takes effect on the next call instead of the next deploy.
     */
    protected function secretKey(): string
    {
        return Setting::credential('flutterwave_secret_key', 'services.flutterwave.secret_key');
    }

    public function publicKey(): string
    {
        return Setting::credential('flutterwave_public_key', 'services.flutterwave.public_key');
    }

    public function encryptionKey(): string
    {
        return Setting::credential('flutterwave_encryption_key', 'services.flutterwave.encryption_key');
    }

    protected function webhookHash(): string
    {
        return Setting::credential('flutterwave_webhook_hash', 'services.flutterwave.webhook_hash');
    }

    /**
     * True only when we are safe to short-circuit the gateway entirely.
     *
     * Mock mode is a local development convenience, so it is opt-in by
     * environment rather than opt-out: anything that is not a developer machine
     * or the test suite — staging, demo, production — must talk to the real
     * gateway. An unconfigured key there means "misconfigured", not "free".
     */
    public function isMockMode(): bool
    {
        if (! app()->environment('local', 'testing')) {
            return false;
        }

        return $this->secretKey() === '' || str_contains(strtolower($this->secretKey()), 'mock');
    }

    /**
     * Initialize subscription payment link for a user.
     *
     * Returns null when the gateway could not produce a checkout link so the
     * caller can surface a real error instead of silently bouncing the user.
     */
    public function createSubscriptionLink(
        User $user,
        ?float $amount = null,
        ?string $currency = null
    ): ?string {
        $currency = strtoupper($currency ?: self::PLAN_CURRENCY);
        $amount = $amount ?? $this->pricing->amountFor($currency);

        // A currency with no configured price would let the payer choose what
        // to send, so refuse rather than charge an amount we cannot verify.
        if (! $this->pricing->isSupportedCurrency($currency)) {
            Log::error('Refusing Flutterwave checkout in an unpriced currency', [
                'user_id' => $user->id,
                'currency' => $currency,
            ]);

            return null;
        }

        $txRef = 'flw_sub_' . $user->id . '_' . now()->timestamp;

        if ($this->isMockMode()) {
            return route('subscription.callback', [
                'gateway' => 'flutterwave',
                'tx_ref' => $txRef,
            ]);
        }

        if ($this->secretKey() === '') {
            Log::error('Flutterwave secret key is not configured.');

            return null;
        }

        $response = Http::withToken($this->secretKey())->post("{$this->baseUrl}/payments", [
            'tx_ref' => $txRef,
            'amount' => $amount,
            'currency' => $currency,
            'redirect_url' => route('subscription.callback', ['gateway' => 'flutterwave']),
            'payment_options' => 'card,banktransfer,account',
            'customer' => [
                'email' => $user->email,
                'name' => $user->name,
            ],
            'customizations' => [
                'title' => 'GUARANTEED CORRECT Monthly Subscription',
                'description' => 'Full access to AI Top 5, Top 10 lists & Expert Picks',
            ],
        ]);

        if (! $response->successful()) {
            Log::error('Flutterwave payment init failed', [
                'user_id' => $user->id,
                'status' => $response->status(),
            ]);

            return null;
        }

        return $response->json('data.link');
    }

    /**
     * Confirm with Flutterwave that a transaction was actually paid.
     *
     * The browser redirect carries no proof of payment, so the reference from
     * the query string is verified server-to-server before granting access.
     */
    public function verifyTransaction(string $txRef, User $user): ?string
    {
        if ($txRef === '') {
            return null;
        }

        if ($this->isMockMode()) {
            return $txRef;
        }

        $response = Http::withToken($this->secretKey())
            ->get("{$this->baseUrl}/transactions/verify_by_reference", ['tx_ref' => $txRef]);

        if (! $response->successful()) {
            Log::warning('Flutterwave verification request failed', [
                'tx_ref' => $txRef,
                'status' => $response->status(),
            ]);

            return null;
        }

        $data = $response->json('data') ?? [];

        // Checkout can charge in any priced currency, so verification compares
        // what cleared against the price for *that* currency rather than a
        // single hardcoded one — otherwise a legitimate dollar payment is
        // rejected, or a naira amount is accepted against a dollar price.
        $paidCurrency = strtoupper((string) ($data['currency'] ?? ''));
        $expected = $this->pricing->amountFor($paidCurrency);

        $isPaid = ($data['status'] ?? '') === 'successful'
            && $expected > 0
            && (float) ($data['amount'] ?? 0) >= $expected;

        // The payer must be the person we are about to upgrade.
        $paidByUser = strcasecmp((string) ($data['customer']['email'] ?? ''), $user->email) === 0;

        if (! $isPaid || ! $paidByUser) {
            Log::warning('Flutterwave transaction rejected', [
                'tx_ref' => $txRef,
                'user_id' => $user->id,
                'paid' => $isPaid,
                'email_match' => $paidByUser,
            ]);

            return null;
        }

        return (string) ($data['id'] ?? $txRef);
    }

    /**
     * Verify the `verif-hash` header Flutterwave sends with every webhook.
     */
    public function verifyWebhookSignature(Request $request): bool
    {
        if ($this->webhookHash() === '') {
            Log::error('Flutterwave webhook hash is not configured; rejecting webhook.');

            return false;
        }

        $signature = (string) $request->header('verif-hash', '');

        return $signature !== '' && hash_equals($this->webhookHash(), $signature);
    }

    /**
     * Process a verified webhook payload.
     */
    public function handleWebhook(array $payload): void
    {
        $event = $payload['event'] ?? '';
        $data = $payload['data'] ?? [];
        $email = $data['customer']['email'] ?? null;

        if (! $email) {
            return;
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return;
        }

        if ($event === 'charge.completed' && ($data['status'] ?? '') === 'successful') {
            // A valid signature proves the payload came from Flutterwave, not
            // that it paid for a subscription. Hold it to the same price check
            // the callback path applies.
            $expected = $this->pricing->amountFor(strtoupper((string) ($data['currency'] ?? '')));

            if ($expected <= 0 || (float) ($data['amount'] ?? 0) < $expected) {
                Log::warning('Flutterwave webhook charge below subscription price; ignoring', [
                    'user_id' => $user->id,
                    'amount' => $data['amount'] ?? null,
                    'currency' => $data['currency'] ?? null,
                ]);

                return;
            }

            $this->subscriptions->activate(
                $user,
                'flutterwave',
                (string) ($data['id'] ?? 'flw_' . now()->timestamp)
            );
        } elseif ($event === 'invoice.payment_failed') {
            $this->subscriptions->markPastDue($user);
        }
    }
}
