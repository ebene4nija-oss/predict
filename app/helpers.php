<?php

if (!function_exists('uppercase_market')) {
    function uppercase_market(string $market): string
    {
        return match($market) {
            'win_draw_loss' => 'Match Outcome',
            'gg' => 'Both Teams Score (GG)',
            'over_2_5' => 'Over 2.5 Goals',
            default => $market,
        };
    }
}

if (!function_exists('uppercase_market_name')) {
    function uppercase_market_name(string $market): string
    {
        return match($market) {
            'win_draw_loss' => 'Match Winner',
            'gg' => 'Both Teams Score (GG)',
            'over_2_5' => 'Over 2.5 Goals',
            default => $market,
        };
    }
}

if (!function_exists('get_market_label')) {
    function get_market_label(string $market): string
    {
        return match($market) {
            'win_draw_loss' => 'Match Outcome (W/D/L)',
            'gg' => 'Both Teams To Score (GG)',
            'over_2_5' => 'Over 2.5 Goals',
            default => $market,
        };
    }
}

if (!function_exists('uppercase_mkt')) {
    function uppercase_mkt(string $m): string
    {
        return match($m) {
            'win_draw_loss' => 'WDL',
            'gg' => 'GG',
            'over_2_5' => 'O2.5',
            default => $m,
        };
    }
}
