<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;

/**
 * Live credential checks for every third-party integration.
 *
 * Each test performs the cheapest authenticated call the vendor offers — a
 * listing or token endpoint, never anything that bills, sends, or mutates —
 * so an admin can confirm a key works without waiting for the nightly
 * pipeline to fail silently.
 *
 * Vendor responses are summarised, never echoed verbatim: some providers
 * include the submitted key in their error payloads, and this output is
 * rendered into an HTML page.
 */
class IntegrationTester
{
    public const SERVICES = [
        'claude' => 'Claude / Anthropic',
        'openai' => 'ChatGPT / OpenAI',
        'gemini' => 'Gemini',
        'kimi' => 'Moonshot / Kimi',
        'football_data' => 'football-data.org',
        'telegram' => 'Telegram Bot',
        'flutterwave' => 'Flutterwave',
        'paypal' => 'PayPal',
    ];

    /** @return array{ok: bool, message: string} */
    public function test(string $service): array
    {
        return match ($service) {
            'claude' => $this->claude(),
            'openai' => $this->openai(),
            'gemini' => $this->gemini(),
            'kimi' => $this->kimi(),
            'football_data' => $this->footballData(),
            'telegram' => $this->telegram(),
            'flutterwave' => $this->flutterwave(),
            'paypal' => $this->paypal(),
            default => $this->fail('Unknown integration.'),
        };
    }

    protected function openai(): array
    {
        $key = Setting::credential('openai_api_key', 'services.openai.key');

        if ($key === '') {
            return $this->fail('No API key set.');
        }

        return $this->call(
            fn () => Http::withToken($key)->timeout(15)->get('https://api.openai.com/v1/models'),
            fn ($r) => 'Key valid. '.count($r->json('data', [])).' models available.',
        );
    }

    protected function kimi(): array
    {
        $key = Setting::credential('kimi_api_key', 'services.kimi.key');

        if ($key === '') {
            return $this->fail('No API key set.');
        }

        return $this->call(
            fn () => Http::withToken($key)->timeout(15)->get('https://api.moonshot.cn/v1/models'),
            fn ($r) => 'Key valid. '.count($r->json('data', [])).' models available.',
        );
    }

    protected function claude(): array
    {
        $key = Setting::credential('claude_api_key', 'services.claude.key');

        if ($key === '') {
            return $this->fail('No API key set.');
        }

        return $this->call(
            fn () => Http::withHeaders([
                'x-api-key' => $key,
                'anthropic-version' => '2023-06-01',
            ])->timeout(15)->get('https://api.anthropic.com/v1/models'),
            fn ($r) => 'Key valid. '.count($r->json('data', [])).' models available.',
        );
    }

    protected function gemini(): array
    {
        $key = Setting::credential('gemini_api_key', 'services.gemini.key');

        if ($key === '') {
            return $this->fail('No API key set.');
        }

        return $this->call(
            fn () => Http::timeout(15)->get('https://generativelanguage.googleapis.com/v1beta/models', ['key' => $key]),
            fn ($r) => 'Key valid. '.count($r->json('models', [])).' models available.',
        );
    }

    protected function footballData(): array
    {
        $token = Setting::credential('football_data_token', 'services.football_data.token');

        if ($token === '') {
            return $this->fail('No token set. Without it there are no fixtures.');
        }

        return $this->call(
            fn () => Http::withHeaders(['X-Auth-Token' => $token])
                ->timeout(15)->get('https://api.football-data.org/v4/competitions'),
            fn ($r) => 'Token valid. '.count($r->json('competitions', [])).' competitions visible.',
        );
    }

    protected function telegram(): array
    {
        $token = Setting::credential('telegram_bot_token', 'services.telegram.bot_token');

        if ($token === '') {
            return $this->fail('No bot token set.');
        }

        return $this->call(
            fn () => Http::timeout(15)->get("https://api.telegram.org/bot{$token}/getMe"),
            fn ($r) => 'Connected as @'.$r->json('result.username', 'unknown').'.',
        );
    }

    protected function flutterwave(): array
    {
        $key = Setting::credential('flutterwave_secret_key', 'services.flutterwave.secret_key');

        if ($key === '') {
            return $this->fail('No secret key set. Flutterwave checkout is disabled.');
        }

        $result = $this->call(
            fn () => Http::withToken($key)->timeout(15)->get('https://api.flutterwave.com/v3/banks/NG'),
            fn ($r) => 'Secret key valid.',
        );

        if ($result['ok'] && Setting::credential('flutterwave_webhook_hash', 'services.flutterwave.webhook_hash') === '') {
            $result['message'] .= ' Warning: no secret hash set, so webhooks are rejected and payments never activate.';
        }

        return $result;
    }

    protected function paypal(): array
    {
        $clientId = Setting::credential('paypal_client_id', 'services.paypal.client_id');
        $secret = Setting::credential('paypal_secret', 'services.paypal.secret');

        if ($clientId === '' || $secret === '') {
            return $this->fail('Client ID or secret missing. PayPal checkout is disabled.');
        }

        $mode = Setting::credential('paypal_mode', 'services.paypal.mode') ?: 'sandbox';
        $base = $mode === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

        $result = $this->call(
            fn () => Http::withBasicAuth($clientId, $secret)
                ->asForm()->timeout(15)
                ->post("{$base}/v1/oauth2/token", ['grant_type' => 'client_credentials']),
            fn ($r) => "Credentials valid in {$mode} mode; access token issued.",
        );

        if ($result['ok'] && Setting::credential('paypal_webhook_id', 'services.paypal.webhook_id') === '') {
            $result['message'] .= ' Warning: no webhook ID set, so webhooks are rejected and subscriptions never activate.';
        }

        return $result;
    }

    /**
     * Run a request, turning transport failures and HTTP errors into the same
     * shape. A timeout or DNS failure is a configuration problem the admin
     * needs to see, not an exception page.
     */
    protected function call(callable $request, callable $describe): array
    {
        try {
            $response = $request();
        } catch (\Throwable $e) {
            return $this->fail('Could not reach the service: '.class_basename($e).'.');
        }

        if ($response->successful()) {
            return ['ok' => true, 'message' => $describe($response)];
        }

        return $this->fail(match (true) {
            $response->status() === 401 || $response->status() === 403 => 'Rejected (HTTP '.$response->status().') — the credential is wrong or lacks permission.',
            $response->status() === 429 => 'Rate limited (HTTP 429) — the credential works but you are over quota.',
            default => 'Failed with HTTP '.$response->status().'.',
        });
    }

    protected function fail(string $message): array
    {
        return ['ok' => false, 'message' => $message];
    }
}
