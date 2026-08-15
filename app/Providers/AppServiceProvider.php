<?php

namespace App\Providers;

use App\Contracts\FixtureProvider;
use App\Models\Setting;
use App\Services\Fixtures\FootballDataProvider;
use App\Services\Fixtures\SampleFixtureProvider;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (file_exists(app_path('helpers.php'))) {
            require_once app_path('helpers.php');
        }

        // Swapping fixture vendors is one admin setting plus one class. Resolved
        // lazily, so the settings table is only touched when a provider is
        // actually needed rather than on every boot.
        $this->app->bind(FixtureProvider::class, function () {
            return match (Setting::credential('fixture_provider', 'services.fixtures.provider')) {
                'sample' => new SampleFixtureProvider,
                default => new FootballDataProvider,
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Spray protection across the auth endpoints. This is per-IP and so
        // says nothing about any single account being attacked from many
        // addresses — that is {@see \App\Support\LoginThrottle}'s job.
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        $this->assertMailIsDeliverable();
    }

    /**
     * Refuse to run production on a mailer that silently discards mail.
     *
     * Password resets and email verification are the only routes back into a
     * locked-out paid account. On the `log` or `array` drivers those mails go
     * nowhere while every screen still reports success, so the failure is
     * invisible until a user contacts support. Fail loudly at boot instead.
     */
    protected function assertMailIsDeliverable(): void
    {
        if (! $this->app->environment('production')) {
            return;
        }

        $mailer = config('mail.default');

        if (in_array($mailer, ['log', 'array'], true)) {
            throw new \RuntimeException(
                "MAIL_MAILER is set to '{$mailer}' in production: password reset and "
                .'verification emails would be discarded. Configure a real mail transport.'
            );
        }
    }
}
