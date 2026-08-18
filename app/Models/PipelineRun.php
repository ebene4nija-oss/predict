<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One manually triggered run of the prediction pipeline, with its transcript.
 *
 * The dashboard button dispatches to the queue, so nothing about the run is
 * visible from the request that started it. This model is what the live log
 * panel polls: a status, the step in progress, and an append-only list of
 * lines. See {@see \App\Support\PipelineProgress} for how lines get written.
 */
class PipelineRun extends Model
{
    use HasFactory;

    public const STATUS_QUEUED = 'queued';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /**
     * How long a running run may go silent before the panel calls it dead.
     *
     * A worker killed mid-job (timeout, cPanel process limit, a deploy) never
     * gets to write a failure, so the run would otherwise spin forever.
     */
    public const STALL_MINUTES = 10;

    /** Runs kept in history; older ones are pruned when a new run starts. */
    public const KEEP_RUNS = 20;

    protected $fillable = [
        'user_id',
        'status',
        'step',
        'days',
        'trigger',
        'error',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'days' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(PipelineRunLine::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Append a transcript line.
     *
     * Deliberately never throws: this is called from inside ingestion, and a
     * logging failure must not be the thing that breaks a pipeline run.
     */
    public function log(string $message, string $level = PipelineRunLine::LEVEL_INFO): void
    {
        try {
            $this->lines()->create([
                'level' => $level,
                'message' => mb_substr(trim($message), 0, 2000),
                'elapsed_ms' => $this->elapsedMs(),
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            // Nothing useful to do here — the run itself matters more.
        }
    }

    public function markRunning(): void
    {
        $this->update([
            'status' => self::STATUS_RUNNING,
            'started_at' => $this->started_at ?? now(),
        ]);
    }

    /** Record entry into a named stage, both as status and as a transcript line. */
    public function markStep(string $step): void
    {
        $this->update(['step' => $step]);
        $this->log($step, PipelineRunLine::LEVEL_STEP);
    }

    public function markCompleted(): void
    {
        if ($this->isFinished()) {
            return;
        }

        $this->update([
            'status' => self::STATUS_COMPLETED,
            'step' => null,
            'finished_at' => now(),
        ]);
    }

    /**
     * Idempotent: a job that throws marks the run failed and then has failed()
     * called by the queue, and the first message is the useful one.
     */
    public function markFailed(string $error): void
    {
        if ($this->isFinished()) {
            return;
        }

        $this->update([
            'status' => self::STATUS_FAILED,
            'error' => mb_substr($error, 0, 2000),
            'finished_at' => now(),
        ]);

        $this->log($error, PipelineRunLine::LEVEL_ERROR);
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED], true);
    }

    /**
     * Running, but nothing has been written for a while — almost always a
     * worker that was killed rather than a step that is genuinely slow.
     */
    public function isStalled(): bool
    {
        if ($this->status !== self::STATUS_RUNNING) {
            return false;
        }

        $last = $this->lines()->max('created_at') ?? $this->started_at ?? $this->created_at;

        return $last !== null && now()->diffInMinutes($last, true) >= self::STALL_MINUTES;
    }

    public function durationSeconds(): ?int
    {
        if (! $this->started_at) {
            return null;
        }

        return (int) $this->started_at->diffInSeconds($this->finished_at ?? now(), true);
    }

    protected function elapsedMs(): int
    {
        $from = $this->started_at ?? $this->created_at ?? now();

        return (int) round($from->diffInMilliseconds(now(), true));
    }

    /** Keep the history short; transcripts are diagnostics, not records. */
    public static function prune(int $keep = self::KEEP_RUNS): void
    {
        $cutoff = static::query()->orderByDesc('id')->skip($keep)->take(1)->value('id');

        if ($cutoff === null) {
            return;
        }

        // Explicit line delete rather than relying on the FK cascade, which is
        // off by default on some MySQL storage configurations.
        $stale = static::query()->where('id', '<=', $cutoff)->pluck('id');

        PipelineRunLine::whereIn('pipeline_run_id', $stale)->delete();
        static::whereIn('id', $stale)->delete();
    }
}
