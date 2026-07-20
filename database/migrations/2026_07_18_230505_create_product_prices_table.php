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
            $table->id();
            $table->foreignId('product_id');
            $table->foreignId('store_id');
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->foreignId('customer_group_id')->nullable()->constrained('customer_groups')->restrictOnDelete();

            $table->decimal('price', 10, 2)->nullable();
            $table->boolean('is_discount')->default(false);
            $table->integer('sort_order')->default(1);
            $table->dateTime('valid_from')->nullable();
            $table->dateTime('valid_until')->nullable();
            $table->integer('valid_quantity')->nullable();

            $table->timestamps();

            $table->index(['store_id', 'currency_id', 'customer_group_id', 'product_id']);
            $table->foreign(['product_id', 'store_id'])->references(['product_id', 'store_id'])->on('product_stores')->cascadeOnDelete();
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
