<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Where a story came from, as distinct from who wrote it.
     *
     * `posts.source` already records human vs AI. These record the outside
     * report an article was written in response to: the guid is the dedupe key
     * so a feed item is never rewritten twice, and the url is the attribution
     * link shown to readers.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('news_source_id')->nullable()->after('author_id')->constrained('news_sources')->nullOnDelete();
            $table->string('origin_guid', 500)->nullable()->after('news_source_id');
            $table->string('origin_url', 500)->nullable()->after('origin_guid');
            $table->string('origin_name')->nullable()->after('origin_url');

            // Hash rather than the raw guid: feed guids run long and are not
            // reliably under an indexable length on MySQL's utf8mb4.
            $table->string('origin_hash', 64)->nullable()->unique()->after('origin_name');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('news_source_id');
            $table->dropUnique(['origin_hash']);
            $table->dropColumn(['origin_guid', 'origin_url', 'origin_name', 'origin_hash']);
        });
    }
};
