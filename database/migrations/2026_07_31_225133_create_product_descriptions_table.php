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
        // Table with data visible only in one store
        Schema::create('product_descriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->references('id')->onDelete('set null');
            $table->foreignId('manufacturer_id')->nullable()->constrained('manufacturers')->references('id')->onDelete('set null');
            // Flags
            $table->boolean('is_active')->default(false);
            $table->boolean('is_available')->default(false);
            $table->dateTime('is_available_from')->nullable();
            $table->dateTime('is_available_to')->nullable();
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
            
            $table->unique(['product_id', 'store_id']); // Enforce only one pair of product + store, also allow foreign composite key for cascade
            $table->index(['store_id', 'is_active', 'is_available', 'product_id'], 'product_admin_lookup');
            $table->index(['product_id', 'store_id', 'is_active', 'sort_order'], 'product_frontend_lookup');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_descriptions');
    }
};
