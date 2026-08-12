<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expert_picks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expert_id')->constrained('experts')->onDelete('cascade');
            $table->foreignId('match_id')->constrained('matches')->onDelete('cascade');
            $table->enum('market', ['win_draw_loss', 'gg', 'over_2_5']);
            $table->string('pick');
            $table->text('rationale')->nullable();
            $table->decimal('confidence', 5, 4)->default(0.7500);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expert_picks');
    }
};
