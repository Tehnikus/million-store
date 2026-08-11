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
        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('sort_order')->default(1);

            $table->unsignedBigInteger('product_attribute_id');
            $table->unsignedBigInteger('attribute_value_id');

            $table->jsonb('name')->nullable()->default('{}');
            $table->jsonb('description')->nullable()->default('{}');
            $table->timestamps();

            $table->index(['product_id', 'store_id', 'sort_order']);

            $table->foreign(['product_attribute_id', 'product_id'])
                ->references(['id', 'product_id'])
                ->on('product_attributes')
                ->cascadeOnDelete();

            $table->foreign(['attribute_value_id', 'store_id'])
                ->references(['id', 'store_id'])
                ->on('attribute_values')
                ->cascadeOnDelete();

            $table->foreign(['product_id', 'store_id'])
                ->references(['product_id', 'store_id'])
                ->on('product_descriptions')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_attribute_values');
    }
};
