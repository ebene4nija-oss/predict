<?php

namespace App\Jobs;

use App\Contracts\FixtureProvider;
use App\Models\GameMatch;
use App\Models\Result;
use App\Services\TrackRecordService;
use App\Support\MarketOutcome;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Settles finished fixtures automatically.
 *
 * Previously results existed only if an admin typed them in, so the public
 * track record silently froze whenever nobody logged in.
 */
class ResultIngestionJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $days = 3) {}

    public function handle(FixtureProvider $provider, TrackRecordService $trackRecord): void
    {
        if (! $provider->isConfigured()) {
            return;
        }

        $settled = 0;

        foreach ($provider->recentResults($this->days) as $result) {
            $match = GameMatch::where('external_id', $result->externalId)->first();

            if (! $match) {
                continue;
            }

            Result::updateOrCreate(
                ['match_id' => $match->id],
                [
                    'home_score' => $result->homeScore,
                    'away_score' => $result->awayScore,
                    'actual_outcome' => MarketOutcome::actual($result->homeScore, $result->awayScore),
                    'settled_at' => $result->finishedAt ?? now(),
                ]
            );

            $match->update(['status' => 'finished']);
            $settled++;
        }

        if ($settled > 0) {
            $trackRecord->flush();
        }

        Log::info('Result ingestion complete', ['provider' => $provider->name(), 'settled' => $settled]);
    }
}
