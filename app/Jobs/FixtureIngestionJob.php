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

    public function __construct(public int $days = 7) {}

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

        $fixtures = $provider->upcomingFixtures($this->days);
        $ingested = 0;

        foreach ($fixtures as $fixture) {
            // Keyed on the provider's fixture id, so the two league meetings
            // of a season stay distinct rather than overwriting each other.
            $match = GameMatch::updateOrCreate(
                ['external_id' => $fixture->externalId],
                $fixture->toAttributes($provider->name()),
            );

            // Never re-predict or re-write the preview for a started fixture.
            if ($match->hasStarted()) {
                continue;
            }

            $predictionService->calculateAndStore($match);
            $previewService->generatePreview($match);
            $ingested++;
        }

        Log::info('Fixture ingestion complete', [
            'provider' => $provider->name(),
            'fetched' => count($fixtures),
            'processed' => $ingested,
        ]);
    }
}
