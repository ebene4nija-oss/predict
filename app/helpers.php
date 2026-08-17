<?php

use App\Support\MarketRegistry;

/*
 * Market label helpers.
 *
 * These were four parallel match statements that each had to grow an arm per
 * market. They now read from MarketRegistry, so an unknown key degrades to the
 * raw key exactly as before instead of silently mislabelling a pick.
 */

if (!function_exists('uppercase_market')) {
    function uppercase_market(string $market): string
    {
        return MarketRegistry::find($market)?->label ?? $market;
    }
}

if (!function_exists('uppercase_market_name')) {
    function uppercase_market_name(string $market): string
    {
        return MarketRegistry::find($market)?->label ?? $market;
    }
}

if (!function_exists('get_market_label')) {
    function get_market_label(string $market): string
    {
        return MarketRegistry::find($market)?->label ?? $market;
    }
}

if (!function_exists('uppercase_mkt')) {
    function uppercase_mkt(string $m): string
    {
        return MarketRegistry::find($m)?->short ?? $m;
    }
}
