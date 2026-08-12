<?php

namespace App\Console\Commands;

use App\Jobs\FixtureIngestionJob;
use App\Jobs\RankingJob;
use App\Jobs\Ai5SelectionJob;
use App\Services\PredictionService;
use App\Services\PreviewGenerationService;
use Illuminate\Console\Command;

class RunPipelineCommand extends Command
{
    protected $signature = 'predictions:run-pipeline';
    protected $description = 'Manually execute the prediction engine pipeline: ingest fixtures, generate Poisson predictions, rank Top 10, and select AI Top 5.';

    public function handle(PredictionService $predictionService, PreviewGenerationService $previewService): int
    {
        $this->info('Starting Prophet AI prediction engine pipeline...');

        $this->info('1. Running Fixture Ingestion & Poisson Prediction Engine...');
        (new FixtureIngestionJob())->handle($predictionService, $previewService);
        $this->info('✓ Fixture ingestion and predictions completed.');

        $this->info('2. Ranking Top 10 Picks per market...');
        (new RankingJob())->handle();
        $this->info('✓ Top 10 market rankings updated.');

        $this->info('3. Selecting AI Top 5 highest conviction picks...');
        (new Ai5SelectionJob())->handle();
        $this->info('✓ AI Top 5 selection completed.');

        $this->newLine();
        $this->info('🎉 Pipeline executed successfully!');

        return Command::SUCCESS;
    }
}
