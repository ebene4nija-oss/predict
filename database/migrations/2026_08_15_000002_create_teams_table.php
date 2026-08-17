<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per club, so a crest is fetched and stored once instead of being
     * re-derived from a team name on every render. Fixtures previously carried
     * clubs as two free-text strings with nowhere to hang a logo.
     */
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();

            // The provider's own team id. Stable across seasons and across the
            // fixtures/standings endpoints, unlike the display name.
            $table->string('external_id')->nullable();
            $table->string('provider')->nullable();

            $table->string('name');
            $table->string('short_name')->nullable();
            $table->string('tla', 8)->nullable();
            $table->string('crest_url', 500)->nullable();

            // Set when an admin uploads or pastes a replacement crest. Takes
            // precedence over the provider URL so a re-ingestion cannot undo
            // a manual fix.
            $table->string('custom_crest_url', 500)->nullable();

            $table->timestamps();

            $table->unique(['provider', 'external_id']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
