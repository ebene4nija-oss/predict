<?php

use App\Jobs\Ai5SelectionJob;
use App\Jobs\FixtureIngestionJob;
use App\Jobs\RankingJob;
use App\Jobs\ResultIngestionJob;
use App\Jobs\SendTelegramDailyPicks;
use App\Jobs\SubscriptionExpirySweepJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Ingest fixtures, then rank. withoutOverlapping stops a slow ingestion run
// from being started again on top of itself.
Schedule::job(new FixtureIngestionJob)->dailyAt('02:00')->withoutOverlapping();
Schedule::job(new RankingJob)->dailyAt('02:30')->withoutOverlapping();
Schedule::job(new Ai5SelectionJob)->dailyAt('02:35')->withoutOverlapping();

// Settle finished fixtures so the public track record keeps moving without an
// admin having to type scores in by hand.
Schedule::job(new ResultIngestionJob)->hourly()->withoutOverlapping();

// The daily picks job existed but was never scheduled, so subscribers who
// connected Telegram received nothing.
Schedule::job(new SendTelegramDailyPicks)->dailyAt('09:00')->withoutOverlapping();

// Paid access used to depend entirely on a gateway webhook arriving. This ages
// unrenewed subscriptions out on our own clock, so a dropped notification stops
// meaning free access forever.
Schedule::job(new SubscriptionExpirySweepJob)->hourly()->withoutOverlapping();

// Pageviews accumulate a row per request; keep a rolling window.
Schedule::command('pageviews:prune')->weeklyOn(1, '03:00');
