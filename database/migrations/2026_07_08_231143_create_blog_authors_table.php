<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('blog_authors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();

            $table->jsonb('name')->nullable()->default('{}');
            $table->jsonb('h1')->nullable()->default('{}');
            $table->jsonb('meta_title')->nullable()->default('{}');
            $table->jsonb('meta_description')->nullable()->default('{}');
            $table->jsonb('description_short')->nullable()->default('{}');
            $table->jsonb('description_full')->nullable()->default('{}');
            $table->jsonb('seo_keywords')->nullable()->default('{}');

            // {"path": "blog_authors/3/igor-a1b2c3.jpg", "alt": {"uk": "...", "pl": "..."}}
            $table->jsonb('avatar')->nullable()->default('{}');

            // [{"platform": "facebook", "url": "..."}, {"platform": "instagram", "url": "..."}]
            $table->jsonb('social_links')->nullable()->default('[]');

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_authors');
    }
};
