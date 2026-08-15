<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;

class SitemapController extends Controller
{
    public function sitemapXml()
    {
        $matches = GameMatch::select('id', 'updated_at', 'kickoff_at')
            ->orderBy('kickoff_at', 'desc')
            ->take(200)
            ->get();

        $urls = [
            [
                'loc' => route('home'),
                'lastmod' => now()->toIso8601String(),
                'changefreq' => 'hourly',
                'priority' => '1.0',
            ],
            [
                'loc' => route('top.picks'),
                'lastmod' => now()->toIso8601String(),
                'changefreq' => 'hourly',
                'priority' => '0.9',
            ],
            [
                'loc' => route('expert.picks'),
                'lastmod' => now()->toIso8601String(),
                'changefreq' => 'daily',
                'priority' => '0.8',
            ],
            [
                'loc' => route('expert.leaderboard'),
                'lastmod' => now()->toIso8601String(),
                'changefreq' => 'daily',
                'priority' => '0.8',
            ],
            [
                'loc' => route('track-record'),
                'lastmod' => now()->toIso8601String(),
                'changefreq' => 'daily',
                'priority' => '0.7',
            ],
            [
                'loc' => route('how-ai-works'),
                'lastmod' => now()->startOfMonth()->toIso8601String(),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ],
            [
                'loc' => route('subscription.pricing'),
                'lastmod' => now()->startOfMonth()->toIso8601String(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ],
        ];

        // Legal pages: rarely change, but they need to be indexable — payment
        // gateways check they are publicly reachable.
        foreach (['legal.terms', 'legal.privacy', 'legal.refunds'] as $legalRoute) {
            $urls[] = [
                'loc' => route($legalRoute),
                'lastmod' => now()->startOfMonth()->toIso8601String(),
                'changefreq' => 'yearly',
                'priority' => '0.3',
            ];
        }

        foreach ($matches as $match) {
            $urls[] = [
                'loc' => route('matches.show', $match->id),
                'lastmod' => $match->updated_at ? $match->updated_at->toIso8601String() : now()->toIso8601String(),
                'changefreq' => 'daily',
                'priority' => '0.8',
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $url) {
            $xml .= '<url>';
            $xml .= '<loc>' . e($url['loc']) . '</loc>';
            $xml .= '<lastmod>' . $url['lastmod'] . '</lastmod>';
            $xml .= '<changefreq>' . $url['changefreq'] . '</changefreq>';
            $xml .= '<priority>' . $url['priority'] . '</priority>';
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        // Clean XML format fix
        $xml = str_replace(['<loc>=', '<lastmod>=', '<changefreq>=', '<priority>='], ['<loc>', '<lastmod>', '<changefreq>', '<priority>'], $xml);

        return response($xml, 200)->header('Content-Type', 'text/xml');
    }

    public function robotsTxt()
    {
        $content = "User-agent: *\n";
        $content .= "Allow: /\n";
        $content .= "Disallow: /admin/\n";
        $content .= "Disallow: /account/\n";
        $content .= "Disallow: /webhooks/\n\n";
        $content .= "Sitemap: " . route('sitemap.xml') . "\n";

        return response($content, 200)->header('Content-Type', 'text/plain');
    }
}
