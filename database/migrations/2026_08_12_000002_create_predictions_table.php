<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->onDelete('cascade');
            $table->enum('market', ['win_draw_loss', 'gg', 'over_2_5']);
            $table->string('pick')->nullable(); // e.g. Home, Draw, Away, Yes, No, Over, Under
            $table->decimal('probability', 5, 4); // 0.0000 to 1.0000
            $table->boolean('is_top10')->default(false);
            $table->boolean('is_ai5')->default(false);
            $table->text('rationale')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predictions');
    }
};
