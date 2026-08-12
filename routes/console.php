<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\FixtureIngestionJob;
use App\Jobs\RankingJob;
use App\Jobs\Ai5SelectionJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new FixtureIngestionJob)->dailyAt('02:00');
Schedule::job(new RankingJob)->dailyAt('02:30');
Schedule::job(new Ai5SelectionJob)->dailyAt('02:35');

