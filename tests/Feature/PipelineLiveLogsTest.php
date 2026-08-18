<?php

namespace Tests\Feature;

use App\Jobs\Ai5SelectionJob;
use App\Jobs\RankingJob;
use App\Jobs\RunPipelineJob;
use App\Models\GameMatch;
use App\Models\PipelineRun;
use App\Models\Prediction;
use App\Models\User;
use App\Support\PipelineProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PipelineLiveLogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_and_non_admin_cannot_access_pipeline_live_logs(): void
    {
        $response = $this->get(route('admin.pipeline.index'));
        $response->assertRedirect(route('login'));

        $user = User::factory()->create(['role' => 'free']);
        $response = $this->actingAs($user)->get(route('admin.pipeline.index'));
        $response->assertRedirect(route('home'));
    }

    public function test_admin_visiting_pipeline_index_redirects_to_run(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // When no runs exist yet
        $response = $this->actingAs($admin)->get(route('admin.pipeline.index'));
        $response->assertRedirect();

        $run = PipelineRun::first();
        $this->assertNotNull($run);
        $response->assertRedirect(route('admin.pipeline.show', $run));

        // Visiting again redirects to the existing run
        $response2 = $this->actingAs($admin)->get(route('admin.pipeline.index'));
        $response2->assertRedirect(route('admin.pipeline.show', $run));
    }

    public function test_admin_can_trigger_pipeline_run(): void
    {
        Queue::fake();

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.pipeline.run'), [
            'days' => 7,
        ]);

        $run = PipelineRun::latest('id')->first();
        $this->assertNotNull($run);
        $this->assertSame(PipelineRun::STATUS_QUEUED, $run->status);
        $this->assertSame(7, $run->days);
        $this->assertSame($admin->id, $run->user_id);

        $response->assertRedirect(route('admin.pipeline.show', $run));
        Queue::assertPushed(RunPipelineJob::class);
    }

    public function test_lines_endpoint_returns_json_transcript_and_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $run = PipelineRun::create([
            'user_id' => $admin->id,
            'status' => PipelineRun::STATUS_RUNNING,
            'step' => 'Step 1/3 — Ingesting fixtures',
            'started_at' => now()->subSeconds(5),
        ]);

        $run->log('Connecting to provider...');
        $run->log('Ingested fixture Arsenal vs Chelsea');

        $response = $this->actingAs($admin)->getJson(route('admin.pipeline.lines', $run));

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'step',
                'stalled',
                'finished',
                'error',
                'duration',
                'queue_depth',
                'lines' => [
                    '*' => ['id', 'level', 'message', 'at', 'elapsed'],
                ],
            ]);

        $data = $response->json();
        $this->assertSame(PipelineRun::STATUS_RUNNING, $data['status']);
        $this->assertFalse($data['finished']);
        $this->assertCount(2, $data['lines']);
        $this->assertSame('Connecting to provider...', $data['lines'][0]['message']);

        // Querying lines after the first line id
        $firstId = $data['lines'][0]['id'];
        $responseAfter = $this->actingAs($admin)->getJson(route('admin.pipeline.lines', [$run, 'after' => $firstId]));
        $responseAfter->assertOk();
        $dataAfter = $responseAfter->json();
        $this->assertCount(1, $dataAfter['lines']);
        $this->assertSame('Ingested fixture Arsenal vs Chelsea', $dataAfter['lines'][0]['message']);
    }

    public function test_drain_endpoint_returns_json_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $run = PipelineRun::create([
            'user_id' => $admin->id,
            'status' => PipelineRun::STATUS_QUEUED,
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.pipeline.drain', $run));
        $response->assertOk()->assertJson(['ok' => true]);
    }

    public function test_ranking_and_ai5_jobs_write_to_pipeline_progress(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $run = PipelineRun::create([
            'user_id' => $admin->id,
            'status' => PipelineRun::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        $match = GameMatch::create([
            'home_team' => 'Arsenal',
            'away_team' => 'Chelsea',
            'league' => 'Premier League',
            'kickoff_at' => now()->addDays(2),
            'home_form' => ['gf' => 2.0, 'ga' => 1.0],
            'away_form' => ['gf' => 1.5, 'ga' => 1.2],
        ]);

        Prediction::create([
            'match_id' => $match->id,
            'market' => 'over_2_5',
            'pick' => 'Over 2.5',
            'probability' => 0.85,
            'source' => 'test',
            'published_at' => now(),
        ]);

        PipelineProgress::using($run, function () {
            (new RankingJob)->handle();
            (new Ai5SelectionJob)->handle();
        });

        $lines = $run->lines()->pluck('message')->toArray();

        $this->assertTrue(collect($lines)->contains(function ($msg) {
            return str_contains($msg, 'Ranked 1 top picks') || str_contains($msg, 'AI Pick #1');
        }));
    }

    public function test_cli_pipeline_command_attaches_to_pipeline_run(): void
    {
        \App\Models\Setting::set('fixture_provider', 'sample');

        $run = PipelineRun::create([
            'status' => PipelineRun::STATUS_QUEUED,
            'trigger' => 'cli',
        ]);

        $this->artisan('predictions:run-pipeline', [
            '--days' => 3,
            '--run-id' => $run->id,
        ])->assertExitCode(0);

        $run->refresh();
        $this->assertSame(PipelineRun::STATUS_COMPLETED, $run->status);
        $this->assertGreaterThan(0, $run->lines()->count());
    }
}
