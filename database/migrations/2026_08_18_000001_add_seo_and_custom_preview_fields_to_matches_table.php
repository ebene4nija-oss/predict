<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds rich SEO metadata, custom editorial headlines, and admin preview
     * curation flags to the matches table.
     */
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->string('preview_headline')->nullable()->after('preview_text');
            $table->string('seo_title')->nullable()->after('preview_headline');
            $table->text('seo_description')->nullable()->after('seo_title');
            $table->text('seo_keywords')->nullable()->after('seo_description');
            $table->string('preview_status')->default('published')->after('preview_source');
            $table->boolean('is_preview_custom')->default(false)->after('preview_status');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn([
                'preview_headline',
                'seo_title',
                'seo_description',
                'seo_keywords',
                'preview_status',
                'is_preview_custom',
            ]);
        });
    }
};
