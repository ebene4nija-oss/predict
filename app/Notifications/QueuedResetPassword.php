<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * The framework's reset-password mail, pushed onto the queue.
 *
 * Sending inline holds the HTTP request open for the length of the SMTP
 * handshake, so a slow or briefly unreachable mail host turns "forgot my
 * password" into a timeout for the user. Queued, the request returns
 * immediately and delivery is retried by the worker.
 */
class QueuedResetPassword extends ResetPassword implements ShouldQueue
{
    use Queueable;

    /** Retry a failing mail host rather than dropping the only way back in. */
    public int $tries = 3;

    /** @var array<int, int> Seconds to wait between attempts. */
    public array $backoff = [60, 300];
}
