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
        Schema::create('product_descriptions', function (Blueprint $table) {
            $table->id(); // Surrogate PK, consistent with product_stores/product_prices

            // Single FK to product_stores.id instead of product_id+store_id.
            // cascadeOnDelete here means: remove product from a store -> its
            // store-specific description is removed automatically, no orphan rows.
            $table->foreignId('product_store_id')->constrained('product_stores')->cascadeOnDelete();

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

            // One description row per (product, store) — enforced via the single
            // product_store_id FK, since product_stores already guarantees
            // (product_id, store_id) uniqueness upstream.
            $table->unique('product_store_id');
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
