<?php

namespace App\Support;

use App\Models\Setting;

/**
 * The public Telegram identifiers — bot, channel, support contact.
 *
 * These are settable from the admin dashboard, with the .env values acting as
 * the bootstrap fallback (see {@see Setting::credential()}). Reading
 * config('services.telegram.*') directly anywhere in the app silently ignores
 * whatever the admin typed, which is what used to happen on the join banner.
 *
 * Admins type the handle either way round — "@Picks" and "Picks" are the same
 * channel — so every value is normalised here rather than at each call site.
 */
class TelegramHandles
{
    public static function botUsername(): string
    {
        return static::normalise(Setting::credential('telegram_bot_username', 'services.telegram.bot_username'));
    }

    public static function channelUsername(): string
    {
        return static::normalise(Setting::credential('telegram_channel_username', 'services.telegram.channel_username'));
    }

    /** The bot handle as displayed to users, with the leading "@". */
    public static function botHandle(): string
    {
        return '@'.static::botUsername();
    }

    /** The channel handle as displayed to users, with the leading "@". */
    public static function channelHandle(): string
    {
        $raw = Setting::credential('telegram_channel_username', 'services.telegram.channel_username');
        if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
            $path = parse_url($raw, PHP_URL_PATH);
            return '@'.ltrim($path ?: $raw, '/@+');
        }

        return '@'.static::channelUsername();
    }

    public static function botUrl(): string
    {
        $raw = Setting::credential('telegram_bot_username', 'services.telegram.bot_username');
        if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
            return $raw;
        }

        return 'https://t.me/'.static::botUsername();
    }

    public static function channelUrl(): string
    {
        $raw = Setting::credential('telegram_channel_username', 'services.telegram.channel_username');
        if ($raw === '') {
            return '';
        }

        if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
            return $raw;
        }

        return 'https://t.me/'.static::channelUsername();
    }

    /**
     * Where the "Admin Support" button points. Stored as a full URL because
     * support is not always a t.me handle — it may be a group invite link.
     */
    public static function supportUrl(): string
    {
        $url = Setting::credential('telegram_admin_support_url', 'services.telegram.admin_support_url');

        if ($url === '') {
            return '';
        }

        // An admin who types a bare handle here means t.me, not a relative path.
        return str_starts_with($url, 'http') ? $url : 'https://t.me/'.static::normalise($url);
    }

    protected static function normalise(?string $value): string
    {
        return ltrim(trim((string) $value), '@');
    }
}
