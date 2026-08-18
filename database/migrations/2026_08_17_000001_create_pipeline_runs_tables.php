<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Progress records for manually triggered pipeline runs.
 *
 * The pipeline runs on the queue, so the admin who pressed the button has no
 * way to see what happened — the request returns before ingestion has fetched
 * its first fixture. These two tables are the transcript the dashboard polls.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pipeline_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('queued')->index();
            $table->string('step')->nullable();
            $table->unsignedSmallInteger('days')->nullable();
            $table->string('trigger')->default('admin');
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('pipeline_run_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_run_id')->constrained()->cascadeOnDelete();
            $table->string('level', 20)->default('info');
            $table->text('message');
            $table->unsignedInteger('elapsed_ms')->nullable();
            $table->timestamp('created_at')->nullable();

            // Every poll asks "lines for this run with an id above N", which is
            // this index exactly.
            $table->index(['pipeline_run_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pipeline_run_lines');
        Schema::dropIfExists('pipeline_runs');
    }
};
