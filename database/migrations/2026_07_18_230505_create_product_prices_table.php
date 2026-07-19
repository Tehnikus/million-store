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
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id(); // Surrogate PK, consistent with product_stores/product_descriptions

            // Same pattern as product_descriptions: single FK to product_stores.id.
            // No direct FK to products here anymore — product_stores already
            // guarantees the product exists, so this stays the one place
            // that controls cascade deletion for store-scoped product data.
            $table->foreignId('product_store_id')->constrained('product_stores')->cascadeOnDelete();

            // Currency is a reference/lookup table, same as languages elsewhere
            // in the project — must block deletion, not cascade it, or prices
            // would silently disappear if a currency is removed.
            $table->foreignId('currency_id')->constrained('currencies')->cascadeOnDelete();

            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('special_price', 10, 2)->nullable();
            $table->dateTime('special_valid_from')->nullable();
            $table->dateTime('special_valid_until')->nullable();
            $table->integer('special_valid_quantity')->nullable();

            $table->timestamps();

            // One price per (product+store, currency) — a product can't have two different prices in the same store/currency at once.
            $table->unique(['product_store_id', 'currency_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
