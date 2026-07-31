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
        Schema::create('manufacturers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            // Flags
            $table->boolean('is_active')->default(false);
            $table->boolean('show_in_facets')->default(false);
            $table->foreignId('parent_id')->nullable()->constrained('manufacturers')->cascadeOnDelete();
            $table->integer('sort_order')->default(1);
            // Descriptions
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
            $table->timestamps();

            $table->index(['store_id', 'parent_id', 'is_active', 'show_in_facets', 'sort_order'], 'manufacturers_frontend_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manufacturers');
    }
};
