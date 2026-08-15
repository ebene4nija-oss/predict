<?php

namespace App\Console\Commands;

use App\Models\Pageview;
use Illuminate\Console\Command;

/**
 * Pageviews grow by one row per request forever. Without pruning this becomes
 * the largest table in the database and slows every analytics query.
 */
class PrunePageviewsCommand extends Command
{
    protected $signature = 'pageviews:prune {--days=90 : Retain this many days of raw pageviews}';

    protected $description = 'Delete raw pageview rows older than the retention window';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        // Chunked so a large backlog does not lock the table in one statement.
        $deleted = 0;

        do {
            $batch = Pageview::where('created_at', '<', $cutoff)->limit(1000)->delete();
            $deleted += $batch;
        } while ($batch > 0);

        $this->info("Pruned {$deleted} pageview(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
