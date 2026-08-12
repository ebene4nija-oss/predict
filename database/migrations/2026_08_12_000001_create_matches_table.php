<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->string('home_team');
            $table->string('away_team');
            $table->string('league');
            $table->dateTime('kickoff_at');
            $table->json('home_form')->nullable();
            $table->json('away_form')->nullable();
            $table->text('h2h_summary')->nullable();
            $table->text('injury_notes')->nullable();
            $table->text('preview_text')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
