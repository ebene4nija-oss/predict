<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value'];

    /**
     * Settings holding third-party credentials. These are encrypted at rest so
     * a database dump or a read-only SQL injection does not hand over every
     * integration the platform owns.
     */
    public const SECRET_KEYS = [
        'claude_api_key',
        'gemini_api_key',
        'telegram_bot_token',
        'telegram_webhook_secret',
        'football_data_token',
        'flutterwave_secret_key',
        'flutterwave_public_key',
        'flutterwave_webhook_hash',
        'paypal_client_id',
        'paypal_secret',
        'paypal_webhook_id',
    ];

    protected const CACHE_PREFIX = 'setting.';

    public static function isSecret(string $key): bool
    {
        return in_array($key, self::SECRET_KEYS, true);
    }

    /**
     * A credential, preferring the admin-managed value over the .env fallback.
     *
     * Every integration is configurable from the admin dashboard; the config
     * key remains as a bootstrap fallback so a deployment can be seeded from
     * the environment before anyone logs in.
     *
     * Never throws: credentials are read from service constructors that may
     * run before the settings table exists (a fresh install mid-migration),
     * and a missing table should degrade to "unconfigured", not a 500.
     */
    public static function credential(string $key, ?string $configKey = null): string
    {
        try {
            $value = static::get($key);
        } catch (\Throwable) {
            $value = null;
        }

        if (filled($value)) {
            return trim((string) $value);
        }

        return $configKey ? trim((string) config($configKey, '')) : '';
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = Cache::rememberForever(
            self::CACHE_PREFIX.$key,
            fn () => static::where('key', $key)->value('value') ?? false
        );

        // `false` is the sentinel for "no row"; a stored empty string is a
        // real value and must not fall through to the default.
        if ($value === false) {
            return $default;
        }

        if (! self::isSecret($key)) {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            // Written before encryption was introduced, or with a different
            // APP_KEY. Return it as-is so the app keeps working; it will be
            // encrypted on the next save.
            Log::warning('Setting could not be decrypted; treating as plaintext.', ['key' => $key]);

            return $value;
        }
    }

    public static function set(string $key, mixed $value): void
    {
        $value = is_array($value) ? json_encode($value) : (string) $value;

        static::updateOrCreate(
            ['key' => $key],
            ['value' => self::isSecret($key) ? Crypt::encryptString($value) : $value]
        );

        Cache::forget(self::CACHE_PREFIX.$key);
    }

    protected static function booted(): void
    {
        // Direct model writes bypass set(); keep the cache honest anyway.
        static::saved(fn (self $setting) => Cache::forget(self::CACHE_PREFIX.$setting->key));
        static::deleted(fn (self $setting) => Cache::forget(self::CACHE_PREFIX.$setting->key));
    }
}
