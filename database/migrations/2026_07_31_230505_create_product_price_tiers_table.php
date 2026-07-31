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
        Schema::create('product_price_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id');
            $table->foreignId('store_id');
            $table->foreignId('customer_group_id')->nullable()->constrained('customer_groups')->cascadeOnDelete();
            $table->jsonb('name')->default('{}');
            $table->boolean('is_discount')->default(false);
            $table->integer('priority')->default(1);
            $table->dateTime('valid_from')->nullable();
            $table->dateTime('valid_until')->nullable();
            $table->integer('valid_quantity')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'store_id', 'customer_group_id', 'priority']);
            $table->foreign(['product_id', 'store_id'])->references(['product_id', 'store_id'])->on('product_descriptions')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_price_tiers');
    }
};
