<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `market` was an enum of three values on both tables.
 *
 * The market catalogue is now a registry that grows — win, first-half goals,
 * half-time result, corners and cards — and an enum makes every addition a
 * schema migration on a table with production rows, on two different drivers
 * that alter enums in incompatible ways. The vocabulary is validated in the
 * application (MarketRegistry) where it can grow cheaply.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->string('market')->change();
        });

        Schema::table('expert_picks', function (Blueprint $table) {
            $table->string('market')->change();
        });
    }

    /**
     * Rolling back restores the original three-value vocabulary, so rows using
     * any market added since must be removed first — the enum cannot hold them.
     */
    public function down(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->enum('market', ['win_draw_loss', 'gg', 'over_2_5'])->change();
        });

        Schema::table('expert_picks', function (Blueprint $table) {
            $table->enum('market', ['win_draw_loss', 'gg', 'over_2_5'])->change();
        });
    }
};
