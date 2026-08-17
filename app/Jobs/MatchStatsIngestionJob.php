<?php

namespace App\Jobs;

use App\Services\Stats\MatchStatsService;
use App\Services\TrackRecordService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Keeps the corner and card markets fed.
 *
 * Two jobs in one pass because they read the same files: refresh the per-club
 * rates the markets are modelled from, and settle the per-match totals the
 * published picks are graded against.
 */
class MatchStatsIngestionJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $days = 7) {}

    public function handle(MatchStatsService $stats, TrackRecordService $trackRecord): void
    {
        $settled = $stats->settleCounts($this->days);
        $teams = $stats->refreshTeamRates();

        // Settling adds gradeable picks to markets that had none, which changes
        // the published figures.
        if ($settled > 0) {
            $trackRecord->flush();
        }

        Log::info('Match stats ingestion complete', [
            'results_settled' => $settled,
            'teams_updated' => $teams,
        ]);
    }
}
