<?php

namespace App\Services;

use App\Support\FeedItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetches and parses a syndication feed.
 *
 * Handles RSS 2.0 and Atom, which between them cover essentially every
 * football news feed. Returns normalised FeedItems or an empty array — a
 * broken or hostile feed must never take the scheduler down with it.
 */
class RssFeedReader
{
    /** Entries read per fetch, before freshness and dedupe filtering. */
    protected const MAX_ENTRIES = 40;

    /**
     * @return array<int, FeedItem>
     */
    public function read(string $url): array
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => config('app.name').' feed reader'])
                ->retry(2, 500, throw: false)
                ->get($url);
        } catch (\Throwable $e) {
            Log::warning('Feed fetch failed', ['url' => $url, 'message' => $e->getMessage()]);

            return [];
        }

        if (! $response->successful()) {
            Log::warning('Feed returned an error status', ['url' => $url, 'status' => $response->status()]);

            return [];
        }

        return $this->parse($response->body());
    }

    /**
     * @return array<int, FeedItem>
     */
    public function parse(string $xml): array
    {
        if (trim($xml) === '') {
            return [];
        }

        $previous = libxml_use_internal_errors(true);

        try {
            // LIBXML_NONET blocks network access during parsing and no entity
            // substitution is requested, so a hostile feed cannot use an
            // external entity to read local files or probe internal hosts.
            $feed = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if ($feed === false) {
            Log::warning('Feed could not be parsed as XML.');

            return [];
        }

        $entries = $this->extractEntries($feed);

        return array_slice($entries, 0, self::MAX_ENTRIES);
    }

    /**
     * @return array<int, FeedItem>
     */
    protected function extractEntries(\SimpleXMLElement $feed): array
    {
        // RSS 2.0 nests items under <channel>; RSS 1.0 puts them at the root;
        // Atom uses <entry>. Take whichever this document actually has.
        $nodes = $feed->channel->item ?? null;

        if ($nodes === null || count($nodes) === 0) {
            $nodes = count($feed->item) > 0 ? $feed->item : $feed->entry;
        }

        $items = [];

        foreach ($nodes ?? [] as $node) {
            $item = $this->toItem($node);

            if ($item !== null) {
                $items[] = $item;
            }
        }

        return $items;
    }

    protected function toItem(\SimpleXMLElement $node): ?FeedItem
    {
        $title = $this->text($node->title);
        $url = $this->link($node);

        // An entry with neither a title nor a link is not a story.
        if ($title === '' && $url === null) {
            return null;
        }

        // Atom calls it id, RSS calls it guid; fall back to the link, then to
        // the title, so an entry is always dedupable by something.
        $guid = $this->text($node->guid) ?: $this->text($node->id) ?: $url ?: $title;

        return new FeedItem(
            guid: $guid,
            title: $title,
            summary: $this->summary($node),
            url: $url,
            publishedAt: $this->publishedAt($node),
        );
    }

    protected function link(\SimpleXMLElement $node): ?string
    {
        $link = $this->text($node->link);

        // Atom carries the URL in an href attribute rather than as text.
        if ($link === '' && isset($node->link['href'])) {
            $link = trim((string) $node->link['href']);
        }

        return $link !== '' ? $link : null;
    }

    /**
     * The entry's own words, stripped to plain text.
     *
     * This is what the rewrite is grounded in; it is never republished as-is.
     */
    protected function summary(\SimpleXMLElement $node): string
    {
        $namespaced = $node->children('http://purl.org/rss/1.0/modules/content/');

        $candidates = [
            $this->text($node->description),
            $this->text($node->summary),
            $this->text($node->content),
            isset($namespaced->encoded) ? trim(strip_tags((string) $namespaced->encoded)) : '',
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== '') {
                // Trimmed hard: feeds carrying a whole article are exactly the
                // case where a model would be tempted to paraphrase line by
                // line rather than write something new.
                return mb_substr($candidate, 0, 1200);
            }
        }

        return '';
    }

    protected function publishedAt(\SimpleXMLElement $node): ?Carbon
    {
        $raw = $this->text($node->pubDate)
            ?: $this->text($node->published)
            ?: $this->text($node->updated)
            ?: $this->text($node->children('http://purl.org/dc/elements/1.1/')->date ?? null);

        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function text(?\SimpleXMLElement $node): string
    {
        return $node === null ? '' : trim(strip_tags((string) $node));
    }
}
