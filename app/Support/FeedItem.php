<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * One entry from a syndication feed, normalised across RSS 2.0 and Atom.
 */
readonly class FeedItem
{
    public function __construct(
        public string $guid,
        public string $title,
        public string $summary,
        public ?string $url = null,
        public ?CarbonInterface $publishedAt = null,
    ) {}

    /**
     * Stable dedupe key.
     *
     * Hashed because feed guids are frequently long URLs, and MySQL cannot
     * index a 500-character utf8mb4 column. Keyed on the guid alone so the
     * same story is not rewritten again when a publisher edits its headline.
     */
    public function hash(): string
    {
        return hash('sha256', $this->guid);
    }

    /**
     * Whether the entry is recent enough to be worth writing about.
     *
     * A feed with no dates is treated as current: refusing to publish is worse
     * than occasionally covering something slightly stale.
     */
    public function isFresherThan(int $hours): bool
    {
        return $this->publishedAt === null || $this->publishedAt->greaterThan(now()->subHours($hours));
    }
}
