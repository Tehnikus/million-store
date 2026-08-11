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
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('attribute_id');
            $table->unsignedBigInteger('sort_order')->default(1);
            $table->timestamps();

            $table->unique(['id', 'product_id']);
            $table->index(['product_id', 'store_id', 'sort_order']);

            // Composite FK
            $table->foreign(['product_id', 'store_id'])
                ->references(['product_id', 'store_id'])
                ->on('product_descriptions')
                ->cascadeOnDelete();

            $table->foreign(['attribute_id', 'store_id'])
                ->references(['id', 'store_id'])
                ->on('attributes')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_attributes');
    }
};
