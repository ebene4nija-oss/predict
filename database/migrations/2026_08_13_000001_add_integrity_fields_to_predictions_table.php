<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            // Which engine actually produced this pick. 'poisson_fallback'
            // records that Claude was asked and could not answer, so a silent
            // downgrade is visible instead of being indistinguishable from a
            // deliberate statistical run.
            $table->string('source')->default('poisson_xg')->after('market');

            // When the pick was locked. Grading only counts picks published
            // before kickoff, which is what makes the public record auditable.
            $table->timestamp('published_at')->nullable()->after('rationale');

            // Best available decimal price at publication, for value and ROI.
            $table->decimal('odds', 6, 2)->nullable()->after('published_at');

            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->dropIndex(['published_at']);
            $table->dropColumn(['source', 'published_at', 'odds']);
        });
    }
};
