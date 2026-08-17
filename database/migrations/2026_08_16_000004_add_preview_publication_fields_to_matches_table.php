<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Makes preview publication a recorded fact rather than a side effect.
 *
 * The ingestion job rewrote every unstarted fixture's preview on every daily
 * run, so a fixture a week out was sent to the model seven times and its
 * published text replaced seven times — seven times the spend, on copy written
 * specifically to be indexed, which search engines then saw change daily.
 *
 * Recording when a preview was written, whether it has had its near-kickoff
 * refresh, and whether the model actually produced it lets the job write each
 * preview once, refresh it once on fresh data, and retry only the ones that
 * fell back to boilerplate.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->timestamp('preview_generated_at')->nullable()->after('preview_text');
            $table->timestamp('preview_refreshed_at')->nullable()->after('preview_generated_at');

            // 'gemini' or 'fallback'. The fallback is boilerplate written when
            // the model is unreachable; without recording it, an outage would
            // be indistinguishable from a real preview and bake the
            // placeholder in permanently once daily rewrites stop.
            $table->string('preview_source')->nullable()->after('preview_refreshed_at');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn(['preview_generated_at', 'preview_refreshed_at', 'preview_source']);
        });
    }
};
