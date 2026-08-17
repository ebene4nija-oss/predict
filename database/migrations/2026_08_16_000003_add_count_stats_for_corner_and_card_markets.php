<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Corner and card data, for the count markets.
 *
 * Two halves of the same problem: `results` gains the per-match totals the
 * markets are graded against, and `teams` gains the rolling per-game rates they
 * are modelled from. Both come from the football-data.co.uk season CSVs, which
 * carry HC/AC (corners) and HY/AY (yellows) alongside the score.
 *
 * Everything is nullable. A club with no rates is a club we cannot price a
 * corner market for — Champions League sides have no domestic-CSV row — and
 * absent must stay distinguishable from zero, or every unmapped fixture would
 * be modelled as a game with no corners in it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->unsignedSmallInteger('home_corners')->nullable()->after('ht_away_score');
            $table->unsignedSmallInteger('away_corners')->nullable()->after('home_corners');
            $table->unsignedSmallInteger('home_yellows')->nullable()->after('away_corners');
            $table->unsignedSmallInteger('away_yellows')->nullable()->after('home_yellows');
        });

        Schema::table('teams', function (Blueprint $table) {
            // Per game, over the sampled window. "For" is what the club wins or
            // receives itself; "against" is what its opponents do.
            $table->decimal('corners_for', 5, 3)->nullable()->after('custom_crest_url');
            $table->decimal('corners_against', 5, 3)->nullable()->after('corners_for');
            $table->decimal('cards_for', 5, 3)->nullable()->after('corners_against');
            $table->decimal('cards_against', 5, 3)->nullable()->after('cards_for');

            // Sample size behind the rates. A club with three matches played is
            // not evidence, and the model falls back to the league baseline.
            $table->unsignedSmallInteger('stats_matches')->nullable()->after('cards_against');
            $table->timestamp('stats_updated_at')->nullable()->after('stats_matches');

            // The club's name in the stats CSV, which does not match the
            // fixture provider's ("West Ham" vs "West Ham United FC"). Resolved
            // once and stored so the mapping is auditable and an admin can fix
            // a bad match by hand instead of it silently re-breaking.
            $table->string('stats_alias')->nullable()->after('stats_updated_at');
            $table->index('stats_alias');
        });
    }

    public function down(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->dropColumn(['home_corners', 'away_corners', 'home_yellows', 'away_yellows']);
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropIndex(['stats_alias']);
            $table->dropColumn([
                'corners_for', 'corners_against', 'cards_for', 'cards_against',
                'stats_matches', 'stats_updated_at', 'stats_alias',
            ]);
        });
    }
};
