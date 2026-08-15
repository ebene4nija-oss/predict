<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\TrackPageviewsMiddleware::class,
        ]);

        // Gateways and Telegram post server-to-server and cannot carry a CSRF
        // token. Both endpoints authenticate the caller by signature instead.
        $middleware->validateCsrfTokens(except: [
            'webhooks/payment',
            'webhooks/telegram',
        ]);

        $middleware->alias([
            'subscriber' => \App\Http\Middleware\EnsureSubscriber::class,
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
