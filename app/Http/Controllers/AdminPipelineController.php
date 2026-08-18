<?php

namespace App\Http\Controllers;

use App\Jobs\RunPipelineJob;
use App\Models\PipelineRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Trigger the prediction pipeline and stream its transcript back.
 *
 * The pipeline cannot run inside the request — ingestion is minutes of provider
 * and model calls — so the button queues a job and the admin watches the
 * transcript the job writes. See {@see \App\Models\PipelineRun}.
 */
class AdminPipelineController extends Controller
{
    /** Lines returned per poll. Generous: a 7-day run writes a few hundred. */
    protected const LINES_PER_POLL = 500;

    /**
     * Entrypoint for the Pipeline section.
     * Routes directly to the active run or the most recent historical run.
     */
    public function index()
    {
        $this->ensureTablesExist();

        try {
            $active = PipelineRun::query()
                ->whereIn('status', [PipelineRun::STATUS_QUEUED, PipelineRun::STATUS_RUNNING])
                ->latest('id')
                ->first();

            if ($active && ! $active->isStalled()) {
                return redirect()->route('admin.pipeline.show', $active);
            }

            $latest = PipelineRun::latest('id')->first();

            if ($latest) {
                return redirect()->route('admin.pipeline.show', $latest);
            }

            // If no run has ever been created, create an initial run container
            $run = PipelineRun::create([
                'user_id' => auth()->id(),
                'status' => PipelineRun::STATUS_QUEUED,
                'trigger' => 'admin',
            ]);
            $run->log('Pipeline initialized. Click "Run Pipeline" to start.');

            return redirect()->route('admin.pipeline.show', $run);
        } catch (\Throwable $e) {
            return redirect()->route('admin.system')->with('error', 'Database tables for pipeline runs not found. Please click "Run database migrations" in System.');
        }
    }

    public function run(Request $request)
    {
        $this->ensureTablesExist();

        $validated = $request->validate([
            'days' => ['nullable', 'integer', 'min:1', 'max:14'],
        ]);

        try {
            // One pipeline at a time: two concurrent ingestions fight over the same
            // fixtures and double every provider and model call.
            $active = PipelineRun::query()
                ->whereIn('status', [PipelineRun::STATUS_QUEUED, PipelineRun::STATUS_RUNNING])
                ->latest('id')
                ->first();

            if ($active) {
                if (! $active->isStalled()) {
                    return redirect()->route('admin.pipeline.show', $active)
                        ->with('success', 'A pipeline run is already in progress — showing its live log.');
                }

                // Silent for longer than a stall window means the worker died. Close
                // the record out honestly rather than blocking new runs forever.
                $active->markFailed('Abandoned: no output for '.PipelineRun::STALL_MINUTES.' minutes, so the worker is assumed dead.');
            }

            PipelineRun::prune();

            $run = PipelineRun::create([
                'user_id' => $request->user()->id,
                'status' => PipelineRun::STATUS_QUEUED,
                'days' => $validated['days'] ?? null,
                'trigger' => 'admin',
            ]);

            $run->log('Queued by '.$request->user()->name.'. Waiting for the queue worker to pick it up…');

            RunPipelineJob::dispatch($run->id, $run->days);

            return redirect()->route('admin.pipeline.show', $run);
        } catch (\Throwable $e) {
            return redirect()->route('admin.system')->with('error', 'Could not start pipeline: '.$e->getMessage().'. Please run migrations.');
        }
    }

    /**
     * Trigger queue worker on-demand from the UI to drain queued jobs immediately.
     */
    public function drain(PipelineRun $run): JsonResponse
    {
        $this->ensureTablesExist();

        if ($run->status === PipelineRun::STATUS_QUEUED) {
            try {
                \Illuminate\Support\Facades\Artisan::call('queue:work', [
                    '--stop-when-empty' => true,
                    '--max-time' => 30,
                ]);
            } catch (\Throwable $e) {
                // If artisan call fails, we still return JSON with current run status
            }
        }

        try {
            $run->refresh();
            $status = $run->status;
        } catch (\Throwable) {
            $status = 'unknown';
        }

        return response()->json([
            'ok' => true,
            'status' => $status,
            'queue_depth' => $this->queueDepth(),
        ]);
    }

    public function show(PipelineRun $run)
    {
        $this->ensureTablesExist();

        try {
            $recent = PipelineRun::with('user')->latest('id')->take(8)->get();
        } catch (\Throwable) {
            $recent = collect([$run]);
        }

        return view('admin.pipeline.show', [
            'run' => $run,
            'recent' => $recent,
            'queueDepth' => $this->queueDepth(),
            // With the sync driver the job has already run by the time this page
            // loads, so the panel must not sit there waiting for a worker.
            'runsInline' => config('queue.default') === 'sync',
        ]);
    }

    /**
     * Transcript lines above the last id the browser has seen.
     *
     * Status is read *before* the lines deliberately: read the other way round,
     * a run that finishes between the two queries would be reported as complete
     * while its closing lines were still unfetched, and the poller would stop.
     */
    public function lines(Request $request, PipelineRun $run): JsonResponse
    {
        $after = max(0, (int) $request->query('after', 0));

        try {
            $run->refresh();

            $status = $run->status;
            $stalled = $run->isStalled();

            $lines = $run->lines()
                ->where('id', '>', $after)
                ->orderBy('id')
                ->limit(self::LINES_PER_POLL)
                ->get(['id', 'level', 'message', 'elapsed_ms', 'created_at']);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'failed',
                'step' => null,
                'stalled' => false,
                'finished' => true,
                'error' => 'Database tables not migrated: '.$e->getMessage(),
                'duration' => null,
                'queue_depth' => null,
                'lines' => [],
            ]);
        }

        return response()->json([
            'status' => $status,
            'step' => $run->step,
            'stalled' => $stalled,
            'finished' => in_array($status, [PipelineRun::STATUS_COMPLETED, PipelineRun::STATUS_FAILED], true),
            'error' => $run->error,
            'duration' => $run->durationSeconds(),
            'queue_depth' => $this->queueDepth(),
            'lines' => $lines->map(fn ($line) => [
                'id' => $line->id,
                'level' => $line->level,
                'message' => $line->message,
                'at' => optional($line->created_at)->format('H:i:s'),
                'elapsed' => $line->elapsed_ms === null ? null : round($line->elapsed_ms / 1000, 1),
            ])->all(),
        ]);
    }

    /** Pending jobs, or null when the queue is not database-backed. */
    protected function queueDepth(): ?int
    {
        if (config('queue.default') !== 'database') {
            return null;
        }

        try {
            return DB::table('jobs')->count();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Ensure pipeline tables are migrated automatically.
     */
    protected function ensureTablesExist(): void
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('pipeline_runs')) {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            }
        } catch (\Throwable) {
            // Silently ignore if migrations cannot run automatically
        }
    }
}
