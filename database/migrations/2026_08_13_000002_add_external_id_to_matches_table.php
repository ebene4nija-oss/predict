<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            // Fixtures were previously deduplicated on (home_team, away_team,
            // league), which collapses the two league meetings of a season —
            // and every subsequent season — into one row. The provider's own
            // fixture id is the stable key.
            $table->string('external_id')->nullable()->unique()->after('id');
            $table->string('provider')->nullable()->after('external_id');
            $table->string('status')->default('scheduled')->after('kickoff_at');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropUnique(['external_id']);
            $table->dropColumn(['external_id', 'provider', 'status']);
        });
    }
};
