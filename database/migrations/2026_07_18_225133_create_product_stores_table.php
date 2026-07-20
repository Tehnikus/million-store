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
        Schema::create('product_stores', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_available')->default(false);
            $table->dateTime('is_available_from')->nullable();
            $table->dateTime('is_available_to')->nullable();
            $table->timestamps();
            $table->primary(['product_id', 'store_id']);
            $table->index(['store_id', 'is_active', 'is_available', 'product_id'], 'product_stores_catalog_lookup')->where('deleted_at is null');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_stores');
    }
};
