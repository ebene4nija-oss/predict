<?php

/**
 * Subscription pricing, and which gateway serves which market.
 *
 * Stripe does not pay out to Nigerian bank accounts, so Flutterwave carries the
 * Nigerian and wider African base and PayPal covers everyone else. Which one a
 * visitor sees is decided here rather than being left to a form field, because
 * a PayPal-only checkout is unusable for most of the primary market.
 *
 * Amounts are per currency. Any currency listed here is one the checkout may
 * charge in, and one payment verification will accept — so a currency with a
 * wrong or placeholder amount is a real underpayment hole. Review the FX before
 * adding to this list.
 */
return [

    /** Used when the visitor's country cannot be determined. */
    'default_country' => env('PRICING_DEFAULT_COUNTRY', 'NG'),

    'prices' => [
        'NGN' => [
            'amount' => (float) env('PRICE_NGN', 5000),
            'symbol' => '₦',
        ],
        'USD' => [
            'amount' => (float) env('PRICE_USD', 4.99),
            'symbol' => '$',
        ],
    ],

    /**
     * Country (ISO 3166-1 alpha-2) to gateway and currency.
     *
     * The African markets outside Nigeria route through Flutterwave in USD:
     * Flutterwave settles them, and charging in USD avoids publishing a local
     * price per country that nobody is maintaining an FX rate for.
     */
    'countries' => [
        'NG' => ['gateway' => 'flutterwave', 'currency' => 'NGN'],

        'GH' => ['gateway' => 'flutterwave', 'currency' => 'USD'],
        'KE' => ['gateway' => 'flutterwave', 'currency' => 'USD'],
        'ZA' => ['gateway' => 'flutterwave', 'currency' => 'USD'],
        'UG' => ['gateway' => 'flutterwave', 'currency' => 'USD'],
        'TZ' => ['gateway' => 'flutterwave', 'currency' => 'USD'],
        'RW' => ['gateway' => 'flutterwave', 'currency' => 'USD'],
        'ZM' => ['gateway' => 'flutterwave', 'currency' => 'USD'],
        'CI' => ['gateway' => 'flutterwave', 'currency' => 'USD'],
        'SN' => ['gateway' => 'flutterwave', 'currency' => 'USD'],
        'CM' => ['gateway' => 'flutterwave', 'currency' => 'USD'],
        'EG' => ['gateway' => 'flutterwave', 'currency' => 'USD'],
        'MA' => ['gateway' => 'flutterwave', 'currency' => 'USD'],
    ],

    /** Everywhere not listed above. */
    'fallback' => ['gateway' => 'paypal', 'currency' => 'USD'],

    /**
     * Request headers that carry a visitor country code, in priority order.
     * Populated by the CDN in front of the app; absent in local development.
     */
    'country_headers' => [
        'CF-IPCountry',            // Cloudflare
        'CloudFront-Viewer-Country', // AWS CloudFront
        'X-Vercel-IP-Country',
        'X-AppEngine-Country',
    ],

];
