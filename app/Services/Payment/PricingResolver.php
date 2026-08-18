<?php

namespace App\Services\Payment;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Works out which gateway, currency and price a visitor should see.
 *
 * Checkout previously hardcoded Flutterwave in naira unless the form happened
 * to say `paypal`, which left every non-African visitor staring at a naira
 * charge and every Nigerian visitor one stray form value away from a PayPal
 * flow their card cannot complete.
 *
 * Country comes from, in order: an explicit choice the visitor made, the CDN's
 * geo header, then the configured default. Detection is a convenience, never a
 * restriction — the visitor can always override it on the pricing page.
 */
class PricingResolver
{
    /**
     * @return array{country: string, gateway: string, currency: string, amount: float, symbol: string, formatted: string, detected: bool}
     */
    public function resolve(Request $request): array
    {
        [$country, $detected] = $this->country($request);

        $route = config("pricing.countries.{$country}") ?? config('pricing.fallback');

        $currency = $route['currency'];

        return [
            'country' => $country,
            'gateway' => $route['gateway'],
            'currency' => $currency,
            'amount' => $this->amountFor($currency),
            'symbol' => $this->symbolFor($currency),
            'formatted' => $this->format($currency),
            'detected' => $detected,
        ];
    }

    /**
     * The price we expect to be paid in a given currency.
     *
     * Payment verification checks the amount that actually cleared against this,
     * so an unlisted currency returns null and the payment is rejected rather
     * than accepted at whatever the payer chose to send.
     */
    public function amountFor(string $currency): float
    {
        $curr = strtoupper($currency);
        $settingKey = 'price_' . strtolower($curr);
        $stored = Setting::get($settingKey);

        if ($stored !== null && is_numeric($stored) && (float) $stored > 0) {
            return (float) $stored;
        }

        return (float) (config('pricing.prices.'.$curr.'.amount') ?? 0.0);
    }

    public function isSupportedCurrency(string $currency): bool
    {
        return $this->amountFor($currency) > 0;
    }

    public function symbolFor(string $currency): string
    {
        return (string) (config('pricing.prices.'.strtoupper($currency).'.symbol') ?? '');
    }

    public function format(string $currency): string
    {
        $amount = $this->amountFor($currency);

        // Naira and similar are quoted whole; USD keeps its cents.
        $decimals = fmod($amount, 1.0) === 0.0 ? 0 : 2;

        return $this->symbolFor($currency).number_format($amount, $decimals);
    }

    /**
     * @return array{0: string, 1: bool} Country code, and whether it was detected
     *                                   rather than defaulted.
     */
    protected function country(Request $request): array
    {
        // An explicit choice always wins over geo detection.
        $chosen = $this->normalise($request->input('country', ''));

        if ($chosen !== '' && $this->isKnown($chosen)) {
            return [$chosen, true];
        }

        foreach ((array) config('pricing.country_headers', []) as $header) {
            $value = $this->normalise((string) $request->header($header, ''));

            // Cloudflare sends XX for clients it cannot place, and T1 for Tor.
            if ($value !== '' && ! in_array($value, ['XX', 'T1'], true)) {
                return [$value, true];
            }
        }

        $defaultCountry = Setting::get('pricing_default_country') ?: config('pricing.default_country', 'NG');

        return [$this->normalise((string) $defaultCountry), false];
    }

    protected function isKnown(string $country): bool
    {
        // Any two-letter code is acceptable: unlisted ones fall through to the
        // PayPal fallback, which is the correct answer for them anyway.
        return strlen($country) === 2;
    }

    protected function normalise(string $value): string
    {
        return Str::upper(trim($value));
    }
}
