<?php

namespace App\Support;

use App\Models\PipelineRun;
use App\Models\PipelineRunLine;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Log;

/**
 * The transcript sink for a pipeline run in progress.
 *
 * Ingestion and the services it calls need to report progress without knowing
 * anything about the admin dashboard, and without a run object threaded through
 * four constructors that all have their own reasons to exist. So the current
 * run is held here for the duration of {@see self::using()} and every reporting
 * call is a no-op outside it — which is what keeps the nightly scheduled run
 * (no run record, no admin watching) completely unaffected.
 *
 * Two things write lines:
 *  - explicit {@see self::line()} / {@see self::step()} calls, for structure;
 *  - a Log listener, so a warning raised deep inside a provider or the preview
 *    generator shows up in the panel instead of only in laravel.log. That is
 *    usually the line the admin actually needed to see.
 */
class PipelineProgress
{
    protected static ?PipelineRun $run = null;

    /** @var callable|null Extra sink, used by the console command. */
    protected static $echo = null;

    /** Listeners cannot be removed, so register at most once per process. */
    protected static bool $listening = false;

    /**
     * Run $work with $run as the active transcript target.
     *
     * @template TReturn
     * @param  callable(): TReturn  $work
     * @param  callable(string, string): void|null  $echo  Receives (message, level).
     * @return TReturn
     */
    public static function using(PipelineRun $run, callable $work, ?callable $echo = null)
    {
        static::$run = $run;
        static::$echo = $echo;
        static::listen();

        try {
            return $work();
        } finally {
            static::$run = null;
            static::$echo = null;
        }
    }

    public static function current(): ?PipelineRun
    {
        return static::$run;
    }

    public static function active(): bool
    {
        return static::$run !== null;
    }

    public static function line(string $message, string $level = PipelineRunLine::LEVEL_INFO): void
    {
        if (static::$run === null) {
            return;
        }

        static::$run->log($message, $level);

        if (static::$echo) {
            (static::$echo)($message, $level);
        }
    }

    public static function step(string $message): void
    {
        if (static::$run === null) {
            return;
        }

        static::$run->markStep($message);

        if (static::$echo) {
            (static::$echo)($message, PipelineRunLine::LEVEL_STEP);
        }
    }

    public static function success(string $message): void
    {
        static::line($message, PipelineRunLine::LEVEL_SUCCESS);
    }

    public static function warning(string $message): void
    {
        static::line($message, PipelineRunLine::LEVEL_WARNING);
    }

    public static function error(string $message): void
    {
        static::line($message, PipelineRunLine::LEVEL_ERROR);
    }

    /**
     * Mirror application logging into the active run.
     *
     * Registered lazily and left in place: with no run active the callback
     * returns immediately, so the cost outside a pipeline run is a null check.
     */
    protected static function listen(): void
    {
        if (static::$listening) {
            return;
        }

        static::$listening = true;

        Log::listen(function (MessageLogged $event) {
            if (static::$run === null) {
                return;
            }

            $level = static::mapLevel($event->level);

            if ($level === null) {
                return;
            }

            static::line(static::describe($event), $level);
        });
    }

    /** Debug chatter is noise in an operations panel; everything else maps in. */
    protected static function mapLevel(string $level): ?string
    {
        return match (strtolower($level)) {
            'debug' => null,
            'notice', 'info' => PipelineRunLine::LEVEL_INFO,
            'warning' => PipelineRunLine::LEVEL_WARNING,
            default => PipelineRunLine::LEVEL_ERROR,
        };
    }

    /**
     * Flatten a log record into one readable line.
     *
     * Context is worth keeping — "provider not configured" without the provider
     * name sends an admin to the wrong settings field — but only the scalar
     * parts, and only enough of them to stay one line.
     */
    protected static function describe(MessageLogged $event): string
    {
        $context = collect($event->context)
            ->filter(fn ($value) => is_scalar($value) || $value === null)
            ->map(fn ($value, $key) => $key.'='.(is_bool($value) ? ($value ? 'true' : 'false') : (string) $value))
            ->implode(' ');

        return trim($event->message.($context === '' ? '' : ' — '.$context));
    }
}
