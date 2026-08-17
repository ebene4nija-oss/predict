<?php

namespace App\Jobs;

use App\Contracts\FixtureProvider;
use App\Models\GameMatch;
use App\Services\PredictionService;
use App\Services\PreviewGenerationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FixtureIngestionJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  int|null  $days  How far ahead to ingest. Null defers to the
     *                          admin-configured preview lead time, which is
     *                          what the scheduled run wants; an explicit value
     *                          is for one-off backfills.
     */
    public function __construct(public ?int $days = null) {}

    public function handle(
        FixtureProvider $provider,
        PredictionService $predictionService,
        PreviewGenerationService $previewService,
    ): void {
        if (! $provider->isConfigured()) {
            Log::warning('Fixture ingestion skipped: provider not configured.', [
                'provider' => $provider->name(),
            ]);

            return;
        }

        $days = $this->days ?? GameMatch::previewLeadDays();
        $fixtures = $provider->upcomingFixtures($days);
        $ingested = 0;
        $previewed = 0;

        foreach ($fixtures as $fixture) {
            // Keyed on the provider's fixture id, so the two league meetings
            // of a season stay distinct rather than overwriting each other.
            $match = GameMatch::updateOrCreate(
                ['external_id' => $fixture->externalId],
                $fixture->toAttributes($provider->name()) + $fixture->syncTeams($provider->name()),
            );

            // Never re-predict or re-write the preview for a started fixture.
            if ($match->hasStarted()) {
                continue;
            }

            $predictionService->calculateAndStore($match);

            // Written once, refreshed once near kickoff, and retried only when
            // the last attempt fell back to boilerplate. This used to run on
            // every fixture on every nightly pass, so a fixture a week out was
            // rewritten seven times before anyone read it.
            if ($match->needsPreview()) {
                $previewService->generatePreview($match);
                $previewed++;
            }

            $ingested++;
        }

        Log::info('Fixture ingestion complete', [
            'provider' => $provider->name(),
            'lead_days' => $days,
            'fetched' => count($fixtures),
            'processed' => $ingested,
            'previews_written' => $previewed,
        ]);
    }
}
