<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\Post;

class SitemapController extends Controller
{
    public function sitemapXml()
    {
        $matches = GameMatch::select('id', 'home_team', 'away_team', 'league', 'updated_at', 'kickoff_at')
            ->orderBy('kickoff_at', 'desc')
            ->take(250)
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
                'loc' => route('matches.index'),
                'lastmod' => now()->toIso8601String(),
                'changefreq' => 'hourly',
                'priority' => '0.9',
            ],
        ];

        // All 7 listed prediction markets as dedicated indexable landing pages
        foreach (\App\Support\MarketRegistry::listed() as $marketKey => $marketDef) {
            $urls[] = [
                'loc' => route('top.picks', ['market' => $marketKey]),
                'lastmod' => now()->toIso8601String(),
                'changefreq' => 'hourly',
                'priority' => '0.9',
            ];
        }

        $urls[] = [
            'loc' => route('expert.picks'),
            'lastmod' => now()->toIso8601String(),
            'changefreq' => 'daily',
            'priority' => '0.8',
        ];
        $urls[] = [
            'loc' => route('expert.leaderboard'),
            'lastmod' => now()->toIso8601String(),
            'changefreq' => 'daily',
            'priority' => '0.8',
        ];
        $urls[] = [
            'loc' => route('track-record'),
            'lastmod' => now()->toIso8601String(),
            'changefreq' => 'daily',
            'priority' => '0.7',
        ];
        $urls[] = [
            'loc' => route('how-ai-works'),
            'lastmod' => now()->startOfMonth()->toIso8601String(),
            'changefreq' => 'monthly',
            'priority' => '0.6',
        ];
        $urls[] = [
            'loc' => route('subscription.pricing'),
            'lastmod' => now()->startOfMonth()->toIso8601String(),
            'changefreq' => 'weekly',
            'priority' => '0.7',
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

        $urls[] = [
            'loc' => route('blog.index'),
            'lastmod' => now()->toIso8601String(),
            'changefreq' => 'daily',
            'priority' => '0.7',
        ];

        // Published articles only: a draft in the sitemap is a 404 handed
        // straight to a crawler.
        foreach (Post::published()->select('slug', 'updated_at')->orderByDesc('published_at')->take(200)->get() as $post) {
            $urls[] = [
                'loc' => route('blog.show', $post->slug),
                'lastmod' => $post->updated_at?->toIso8601String() ?? now()->toIso8601String(),
                'changefreq' => 'weekly',
                'priority' => '0.6',
            ];
        }

        // Match previews with keyword-rich semantic slugs
        foreach ($matches as $match) {
            $urls[] = [
                'loc' => $match->canonicalUrl(),
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
