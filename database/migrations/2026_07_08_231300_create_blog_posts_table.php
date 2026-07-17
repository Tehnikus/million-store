<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->jsonb('name')->nullable()->default('{}');
            $table->jsonb('h1')->nullable()->default('{}');
            $table->jsonb('meta_title')->nullable()->default('{}');
            $table->jsonb('meta_description')->nullable()->default('{}');
            $table->jsonb('images')->nullable()->default('{}');
            $table->jsonb('description_short')->nullable()->default('{}');
            $table->jsonb('description_full')->nullable()->default('{}');
            $table->jsonb('seo_keywords')->nullable()->default('{}');
            $table->jsonb('faq')->nullable()->default('{}');
            $table->jsonb('how_to')->nullable()->default('{}');
            $table->jsonb('footer')->nullable()->default('{}');
            $table->foreignId('author_id')->nullable()->after('store_id')->constrained('blog_authors')->nullOnDelete();
            $table->boolean('is_active')->default(false);
            $table->integer('sort_order')->nullable();
            $table->index(['id', 'store_id', 'is_active']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
