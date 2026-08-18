<?php

namespace App\Console\Commands;

use App\Contracts\FixtureProvider;
use App\Jobs\FixtureIngestionJob;
use App\Jobs\RankingJob;
use App\Jobs\Ai5SelectionJob;
use App\Models\GameMatch;
use App\Services\PredictionService;
use App\Services\PreviewGenerationService;
use App\Models\PipelineRun;
use App\Support\PipelineProgress;
use Illuminate\Console\Command;

class RunPipelineCommand extends Command
{
    protected $signature = 'predictions:run-pipeline
                            {--days= : How many days ahead to ingest (default: admin setting or 7)}
                            {--run-id= : Link output to an existing PipelineRun record}';

    protected $description = 'Manually execute the prediction engine pipeline: ingest fixtures, generate Poisson predictions, rank Top 10, and select AI Top 5.';

    public function handle(
        FixtureProvider $provider,
        PredictionService $predictionService,
        PreviewGenerationService $previewService,
    ): int {
        $days = (int) ($this->option('days') ?: GameMatch::previewLeadDays());
        $runId = $this->option('run-id');

        $run = $runId ? PipelineRun::find($runId) : PipelineRun::create([
            'status' => PipelineRun::STATUS_QUEUED,
            'days' => $days,
            'trigger' => 'cli',
        ]);

        if ($run) {
            $run->markRunning();
        }

        $echo = function (string $message, string $level) {
            if ($level === 'error') {
                $this->error($message);
            } elseif ($level === 'warning') {
                $this->warn($message);
            } elseif ($level === 'success') {
                $this->info('✓ '.$message);
            } elseif ($level === 'step') {
                $this->line('<fg=cyan;options=bold>'.$message.'</>');
            } else {
                $this->line($message);
            }
        };

        $worker = function () use ($run, $provider, $predictionService, $previewService, $days) {
            PipelineProgress::line('Starting Guaranteed Correct prediction engine pipeline...');
            PipelineProgress::line("Provider: {$provider->name()} · lead time: {$days} days");

            if (! $provider->isConfigured()) {
                $msg = "Fixture provider [{$provider->name()}] is not configured. Set a Football Data token in Admin → Settings or .env.";
                if ($run) {
                    $run->markFailed($msg);
                }
                PipelineProgress::error($msg);

                return Command::FAILURE;
            }

            PipelineProgress::step('Step 1/3 — Ingesting fixtures and calculating predictions');
            (new FixtureIngestionJob($days))->handle($provider, $predictionService, $previewService);
            PipelineProgress::success('Fixture ingestion and predictions completed.');

            PipelineProgress::step('Step 2/3 — Ranking Top 10 lists per market');
            (new RankingJob())->handle();
            PipelineProgress::success('Top 10 market rankings updated.');

            PipelineProgress::step('Step 3/3 — Selecting AI Top 5 highest conviction picks');
            (new Ai5SelectionJob())->handle();
            PipelineProgress::success('AI Top 5 selection completed.');

            $seconds = $run ? ($run->durationSeconds() ?? 0) : 0;
            PipelineProgress::success("Pipeline finished in {$seconds}s.");

            if ($run) {
                $run->markCompleted();
            }

            $this->newLine();
            $this->info('🎉 Pipeline executed successfully!');

            return Command::SUCCESS;
        };

        if ($run) {
            return PipelineProgress::using($run, $worker, $echo);
        }

        return $worker();
    }
}

