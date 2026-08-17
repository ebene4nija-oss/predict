<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive on purpose: home_team/away_team stay as the display strings so
     * every existing query, prompt, and Telegram message keeps working. The
     * ids are the join key for crests and, later, for form lookups that
     * currently match on team name and silently return null on any drift.
     */
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->foreignId('home_team_id')->nullable()->after('away_team')->constrained('teams')->nullOnDelete();
            $table->foreignId('away_team_id')->nullable()->after('home_team_id')->constrained('teams')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('home_team_id');
            $table->dropConstrainedForeignId('away_team_id');
        });
    }
};
