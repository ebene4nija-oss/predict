<?php

namespace App\Support;

use App\Models\Setting;

/**
 * One advertising placement, and the rules about what may be shown in it.
 *
 * The view used to read six settings inline and duplicate its whole markup once
 * for signed-in users and once for guests, which is how the two branches ended
 * up able to drift. The decisions live here instead.
 */
class AdSlot
{
    /** Placements a page may ask for. `type` on the component maps to these. */
    public const PLACEMENTS = ['header', 'in-content', 'sidebar', 'footer'];

    protected function __construct(protected string $placement) {}

    public static function for(string $placement): self
    {
        return new self(
            in_array($placement, self::PLACEMENTS, true) ? $placement : 'header'
        );
    }

    /**
     * Ads are free-tier only. Subscribers see no banner and, in network mode,
     * no third-party tag either — an "ad-free" subscription that still loads
     * the network's script is not ad-free in any sense the subscriber cares
     * about, and it keeps tracking them.
     */
    public function shouldRender(): bool
    {
        if (Setting::get('ad_enabled', '1') !== '1') {
            return false;
        }

        if (auth()->check() && auth()->user()->isSubscriber()) {
            return false;
        }

        // Network mode with nothing pasted in yet would render an empty
        // "Sponsored" frame on every page.
        if ($this->isNetwork() && $this->networkCode() === '') {
            return false;
        }

        return true;
    }

    public function isNetwork(): bool
    {
        return Setting::get('ad_mode', 'house') === 'network';
    }

    /**
     * The network's tag for this placement, falling back to a single shared
     * tag so a responsive unit can be pasted once rather than four times.
     */
    public function networkCode(): string
    {
        $key = 'ad_network_'.str_replace('-', '_', $this->placement);

        $code = trim((string) Setting::get($key, ''));

        return $code !== '' ? $code : trim((string) Setting::get('ad_network_default', ''));
    }

    public function partner(): string
    {
        return (string) Setting::get('ad_partner_name', 'Official Betting Partner');
    }

    public function headline(): string
    {
        return (string) Setting::get('ad_headline', 'High Odds Multiples');
    }

    public function description(): string
    {
        return (string) Setting::get('ad_description', 'Get up to 200% welcome bonus on your first sports deposit.');
    }

    public function ctaText(): string
    {
        return (string) Setting::get('ad_cta_text', 'Claim Bonus');
    }

    public function ctaUrl(): string
    {
        return (string) Setting::get('ad_cta_url', '#');
    }
}
