<?php

namespace App\Jobs;

use App\Contracts\FixtureProvider;
use App\Models\GameMatch;
use App\Models\PipelineRun;
use App\Models\Prediction;
use App\Services\PredictionService;
use App\Services\PreviewGenerationService;
use App\Support\PipelineProgress;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * The admin-triggered pipeline, run as one job against one transcript.
 *
 * The dashboard button used to dispatch Bus::chain([ingest, rank, ai5]), which
 * works but is invisible: three separate jobs, no shared record, and a failure
 * in the first one silently cancels the rest with nothing to show the admin who
 * pressed the button. Running the three stages inline inside a single job gives
 * one place to attach the {@see PipelineRun} transcript, one place to catch a
 * failure, and a guaranteed terminal status.
 *
 * The stages are still the same job classes the scheduler uses — they are
 * invoked directly rather than duplicated, exactly as
 * {@see \App\Console\Commands\RunPipelineCommand} does.
 */
class RunPipelineJob implements ShouldQueue
{
    use Queueable;

    /**
     * Ingestion makes a provider call plus a model call per fixture, so a full
     * seven-day run is minutes, not seconds. A job-level timeout overrides the
     * worker's default 60s, which would otherwise kill every real run.
     */
    public int $timeout = 1800;

    /**
     * Never retried: a half-finished ingestion re-run would rewrite previews
     * that already succeeded, and the transcript is more useful than a retry.
     */
    public int $tries = 1;

    public function __construct(public int $runId, public ?int $days = null) {}

    public function handle(
        FixtureProvider $provider,
        PredictionService $predictionService,
        PreviewGenerationService $previewService,
    ): void {
        $run = PipelineRun::find($this->runId);

        if (! $run || $run->isFinished()) {
            return;
        }

        $run->markRunning();

        PipelineProgress::using($run, function () use ($run, $provider, $predictionService, $previewService) {
            $days = $this->days ?? GameMatch::previewLeadDays();

            PipelineProgress::line("Provider: {$provider->name()} · lead time: {$days} days");

            if (! $provider->isConfigured()) {
                $run->markFailed("Fixture provider [{$provider->name()}] is not configured. Add a Football Data token in Admin → Settings.");

                return;
            }

            PipelineProgress::step('Step 1/3 — Ingesting fixtures and calculating predictions');
            (new FixtureIngestionJob($days))->handle($provider, $predictionService, $previewService);

            PipelineProgress::step('Step 2/3 — Ranking Top 10 lists per market');
            (new RankingJob)->handle();
            PipelineProgress::success(Prediction::where('is_top10', true)->count().' picks promoted to the public Top 10 lists.');

            PipelineProgress::step("Step 3/3 — Selecting AI's Top 5");
            (new Ai5SelectionJob)->handle();
            PipelineProgress::success(Prediction::where('is_ai5', true)->count().' conviction picks selected for AI 5.');

            $seconds = $run->durationSeconds() ?? 0;
            PipelineProgress::success("Pipeline finished in {$seconds}s.");

            // Status flips last so the panel cannot report "completed" before
            // the closing lines exist to be read.
            $run->markCompleted();
        });
    }

    /**
     * Reached on an exception, and on a worker timeout or kill — the case that
     * previously left the dashboard claiming a pipeline was still running.
     */
    public function failed(?\Throwable $exception): void
    {
        PipelineRun::find($this->runId)?->markFailed(
            $exception
                ? class_basename($exception).': '.$exception->getMessage()
                : 'The queue worker stopped before the pipeline finished (timeout or process kill).'
        );
    }
}
