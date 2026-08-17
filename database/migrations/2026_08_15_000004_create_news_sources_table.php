<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('feed_url', 500);

            // Category assigned to every article rewritten from this feed, so
            // a transfer-news feed does not file itself under announcements.
            $table->string('category')->default('news');

            $table->boolean('is_active')->default(true);

            // Ceiling per polling run. Without it, connecting a busy feed would
            // queue a model call for every item in its history at once.
            $table->unsignedSmallInteger('max_per_run')->default(2);

            // Items older than this are ignored, so attaching a feed does not
            // backfill last month's news as today's.
            $table->unsignedSmallInteger('max_age_hours')->default(48);

            $table->timestamp('last_fetched_at')->nullable();
            $table->string('last_error', 500)->nullable();
            $table->unsignedInteger('items_rewritten')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_sources');
    }
};
