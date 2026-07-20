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
            $table->foreignId('product_id');
            $table->foreignId('store_id');

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

            $table->primary(['product_id', 'store_id']);
            $table->foreign(['product_id', 'store_id'])->references(['product_id', 'store_id'])->on('product_stores')->cascadeOnDelete();
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
