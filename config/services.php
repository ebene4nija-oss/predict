<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        // Bootstrap default only; the admin dashboard value wins once set.
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'prediction_model' => env('GEMINI_PREDICTION_MODEL', 'gemini-2.5-flash'),
    ],

    'claude' => [
        'key' => env('CLAUDE_API_KEY'),
        'model' => env('CLAUDE_MODEL', 'claude-opus-5'),
        'effort' => env('CLAUDE_EFFORT', 'medium'),
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o'),
    ],

    'kimi' => [
        'key' => env('KIMI_API_KEY'),
        'model' => env('KIMI_MODEL', 'moonshot-v1-8k'),
    ],

    'fixtures' => [
        // 'football_data' for live fixtures, 'sample' for invented development
        // data (which refuses to run in production).
        'provider' => env('FIXTURE_PROVIDER', 'football_data'),
    ],

    'football_data' => [
        'token' => env('FOOTBALL_DATA_TOKEN', ''),
    ],

    /*
    | Every credential below is also settable from the admin dashboard, which
    | takes precedence. These entries remain as a bootstrap fallback so a fresh
    | deployment can be seeded from the environment before anyone logs in.
    */

    'flutterwave' => [
        'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
        'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
        'encryption_key' => env('FLUTTERWAVE_ENCRYPTION_KEY'),
        // Secret hash configured on the Flutterwave dashboard; sent back as the
        // `verif-hash` header on every webhook.
        'webhook_hash' => env('FLUTTERWAVE_WEBHOOK_HASH', ''),
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'secret' => env('PAYPAL_SECRET'),
        'mode' => env('PAYPAL_MODE', 'sandbox'),
        'plan_id' => env('PAYPAL_PLAN_ID', ''),
        'webhook_id' => env('PAYPAL_WEBHOOK_ID', ''),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN', ''),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET', ''),
        'bot_username' => env('TELEGRAM_BOT_USERNAME', 'GuaranteedCorrectBot'),
        'channel_id' => env('TELEGRAM_CHANNEL_ID', ''),
        'channel_username' => env('TELEGRAM_CHANNEL_USERNAME', 'GuaranteedCorrectPicks'),
        // No default: a hardcoded fallback here shipped every visitor a
        // "Admin Support" button pointing at a channel nobody had registered.
        // Blank hides the button until an admin sets a real contact.
        'admin_support_url' => env('TELEGRAM_ADMIN_SUPPORT_URL', ''),
    ],
];
