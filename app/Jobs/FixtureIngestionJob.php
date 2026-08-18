<?php

namespace App\Jobs;

use App\Contracts\FixtureProvider;
use App\Models\GameMatch;
use App\Services\PredictionService;
use App\Services\PreviewGenerationService;
use App\Support\PipelineProgress;
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
        $skipped = 0;

        PipelineProgress::line(count($fixtures).' fixtures returned for the next '.$days.' days.');

        foreach ($fixtures as $fixture) {
            // Keyed on the provider's fixture id, so the two league meetings
            // of a season stay distinct rather than overwriting each other.
            $match = GameMatch::updateOrCreate(
                ['external_id' => $fixture->externalId],
                $fixture->toAttributes($provider->name()) + $fixture->syncTeams($provider->name()),
            );

            // Never re-predict or re-write the preview for a started fixture.
            if ($match->hasStarted()) {
                $skipped++;
                continue;
            }

            $stored = $predictionService->calculateAndStore($match);

            // Written once, refreshed once near kickoff, and retried only when
            // the last attempt fell back to boilerplate. This used to run on
            // every fixture on every nightly pass, so a fixture a week out was
            // rewritten seven times before anyone read it.
            $wrotePreview = false;

            if ($match->needsPreview()) {
                $previewService->generatePreview($match);
                $previewed++;
                $wrotePreview = true;
            }

            $ingested++;

            // Per-fixture, because this loop is the slow part and a progress
            // panel that only reports totals at the end tells the admin nothing
            // while they are waiting. No-op outside an admin-triggered run.
            PipelineProgress::line(sprintf(
                '%s vs %s (%s) — %d picks%s',
                $match->home_team,
                $match->away_team,
                $match->league,
                count($stored),
                $wrotePreview ? ', preview written' : '',
            ));
        }

        if ($skipped > 0) {
            PipelineProgress::line("{$skipped} fixtures skipped — already kicked off.");
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
