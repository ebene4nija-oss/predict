<?php

namespace App\Support;

use App\Models\Setting;

/**
 * SMTP credentials held in the settings table, applied over config('mail').
 *
 * Shared hosting rarely offers SSH, so an admin who mistypes a mail password
 * would otherwise need a developer and a `config:cache` run to correct it. The
 * .env values stay as the bootstrap fallback, matching how every other
 * integration behaves (see {@see Setting::credential()}).
 *
 * Applied before AppServiceProvider's deliverability guard runs, so SMTP
 * configured here satisfies the guard even when .env still says `log`.
 */
class MailSettings
{
    /** Setting key => dotted config key. */
    public const MAP = [
        'mail_host' => 'mail.mailers.smtp.host',
        'mail_port' => 'mail.mailers.smtp.port',
        'mail_scheme' => 'mail.mailers.smtp.scheme',
        'mail_username' => 'mail.mailers.smtp.username',
        'mail_password' => 'mail.mailers.smtp.password',
        'mail_from_address' => 'mail.from.address',
        'mail_from_name' => 'mail.from.name',
    ];

    public static function apply(): void
    {
        // Nothing configured in the dashboard: leave .env in charge entirely.
        if (! static::isConfigured()) {
            return;
        }

        foreach (static::MAP as $setting => $configKey) {
            $value = static::read($setting);

            if ($value === '') {
                continue;
            }

            config([$configKey => $setting === 'mail_port' ? (int) $value : $value]);
        }

        // A host was supplied, so the admin means to send over SMTP. Without
        // this a .env left on `log` would silently swallow the mail even
        // though the dashboard shows a full SMTP configuration.
        config(['mail.default' => 'smtp']);
    }

    /** True once an admin has supplied at least a host to send through. */
    public static function isConfigured(): bool
    {
        return static::read('mail_host') !== '';
    }

    /**
     * Read a setting without ever throwing.
     *
     * Called from the service provider, which runs before migrations on a
     * fresh install; a missing settings table must degrade to "unconfigured"
     * rather than 500 the whole site.
     */
    protected static function read(string $key): string
    {
        try {
            return trim((string) Setting::get($key, ''));
        } catch (\Throwable) {
            return '';
        }
    }
}
