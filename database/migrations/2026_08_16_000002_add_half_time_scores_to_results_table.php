<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Half-time scores, for the first-half markets.
 *
 * Nullable on purpose. Rows settled before this column existed have no
 * half-time score and never will, and a default of 0 would grade every one of
 * those fixtures as a goalless first half — inventing a settled record out of
 * missing data. Absent reads as unsettleable, which is the truth.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->integer('ht_home_score')->nullable()->after('away_score');
            $table->integer('ht_away_score')->nullable()->after('ht_home_score');
        });
    }

    public function down(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->dropColumn(['ht_home_score', 'ht_away_score']);
        });
    }
};
