<?php

namespace App\Services;

use App\Models\Prediction;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SportyBetService
{
    public const DEFAULT_REGION = 'ng';

    /**
     * Supported SportyBet country codes and domains.
     */
    public const REGIONS = [
        'ng' => ['name' => 'Nigeria', 'domain' => 'https://www.sportybet.com/ng/'],
        'gh' => ['name' => 'Ghana', 'domain' => 'https://www.sportybet.com/gh/'],
        'ke' => ['name' => 'Kenya', 'domain' => 'https://www.sportybet.com/ke/'],
        'ug' => ['name' => 'Uganda', 'domain' => 'https://www.sportybet.com/ug/'],
        'zm' => ['name' => 'Zambia', 'domain' => 'https://www.sportybet.com/zm/'],
        'tz' => ['name' => 'Tanzania', 'domain' => 'https://www.sportybet.com/tz/'],
    ];

    /**
     * Check if SportyBet booking code display is enabled.
     */
    public function isEnabled(): bool
    {
        return (bool) Setting::get('sportybet_booking_code_enabled', '1');
    }

    /**
     * Get active region (e.g. 'ng', 'gh', 'ke').
     */
    public function getRegion(): string
    {
        $region = (string) Setting::get('sportybet_region', self::DEFAULT_REGION);
        return array_key_exists($region, self::REGIONS) ? $region : self::DEFAULT_REGION;
    }

    /**
     * Get SportyBet direct load URL for a booking code.
     */
    public function getLoadUrl(?string $code = null): string
    {
        $region = $this->getRegion();
        $domain = self::REGIONS[$region]['domain'] ?? 'https://www.sportybet.com/ng/';

        $customUrl = Setting::get('sportybet_custom_url');
        if (filled($customUrl)) {
            return str_contains($customUrl, '{code}')
                ? str_replace('{code}', (string) $code, $customUrl)
                : $customUrl;
        }

        if (filled($code)) {
            return "{$domain}?shareCode={$code}";
        }

        return $domain;
    }

    /**
     * Calculate combined accumulator odds for the given collection of predictions.
     */
    public function calculateTotalOdds(Collection $picks): float
    {
        if ($picks->isEmpty()) {
            return 1.0;
        }

        $totalOdds = 1.0;
        foreach ($picks as $pick) {
            $odd = (float) ($pick->odds ?? 0);
            if ($odd <= 1.0) {
                // If explicit odds are not recorded, estimate fair decimal odds from probability
                $prob = max((float) ($pick->probability ?? 0.5), 0.05);
                $odd = round(1.0 / $prob, 2);
            }
            $totalOdds *= $odd;
        }

        return round($totalOdds, 2);
    }

    /**
     * Retrieve or generate booking code info for the Top 5 AI conviction picks.
     *
     * @param Collection|null $picks
     * @return array{
     *     code: string,
     *     total_odds: float,
     *     picks_count: int,
     *     region: string,
     *     region_name: string,
     *     load_url: string,
     *     is_custom: bool,
     *     enabled: bool,
     *     selections: array
     * }
     */
    public function getTop5BookingCodePayload(?Collection $picks = null): array
    {
        if ($picks === null) {
            $picks = Prediction::with(['match.homeClub', 'match.awayClub'])
                ->where('is_ai5', true)
                ->forUpcomingMatches()
                ->orderBy('probability', 'desc')
                ->take(5)
                ->get();
        }

        $customCode = Setting::get('sportybet_top5_booking_code');
        $isCustom = filled($customCode);

        $code = $isCustom
            ? strtoupper(trim((string) $customCode))
            : $this->generateDeterministicBookingCode($picks);

        $totalOdds = $this->calculateTotalOdds($picks);
        $region = $this->getRegion();
        $regionName = self::REGIONS[$region]['name'] ?? 'Nigeria';
        $loadUrl = $this->getLoadUrl($code);

        $selections = $picks->map(function (Prediction $p) {
            return [
                'match' => ($p->match?->home_team ?? 'Home') . ' vs ' . ($p->match?->away_team ?? 'Away'),
                'league' => $p->match?->league ?? 'Football',
                'market' => uppercase_market($p->market),
                'pick' => $p->pick,
                'probability' => round(($p->probability ?? 0) * 100, 1),
                'odds' => $p->odds ?: round(1 / max((float) ($p->probability ?? 0.5), 0.05), 2),
            ];
        })->all();

        return [
            'code' => $code,
            'total_odds' => $totalOdds,
            'picks_count' => $picks->count(),
            'region' => $region,
            'region_name' => $regionName,
            'load_url' => $loadUrl,
            'is_custom' => $isCustom,
            'enabled' => $this->isEnabled(),
            'selections' => $selections,
        ];
    }

    /**
     * Generate a deterministic booking code based on the top 5 picks date and IDs.
     * Matches SportyBet's native 6-character alphanumeric uppercase format (e.g. A5F52E, RURYSY).
     */
    protected function generateDeterministicBookingCode(Collection $picks): string
    {
        if ($picks->isEmpty()) {
            return strtoupper(substr(md5(date('Y-m-d') . 'SPORTY'), 0, 6));
        }

        $seed = $picks->pluck('id')->implode('-') . '-' . date('Y-m-d');
        return strtoupper(substr(md5($seed), 0, 6));
    }

    /**
     * Decode an existing SportyBet booking code via SportyBet API (if credentials/session available).
     */
    public function decodeBookingCode(string $bookingCode): ?array
    {
        $region = $this->getRegion();
        $cookie = Setting::credential('sportybet_cookie');
        $token = Setting::credential('sportybet_token');

        $url = "https://www.sportybet.com/api/{$region}/orders/share/{$bookingCode}";

        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept' => 'application/json, text/plain, */*',
            'Accept-Language' => 'en-US,en;q=0.9',
        ];

        if (filled($cookie)) {
            $headers['Cookie'] = $cookie;
        }

        if (filled($token)) {
            $headers['X-Sporty-Token'] = $token;
        }

        try {
            $response = Http::timeout(10)->withHeaders($headers)->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('SportyBet decode API returned non-200 status', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to decode SportyBet booking code: ' . $e->getMessage());
        }

        return null;
    }
}
