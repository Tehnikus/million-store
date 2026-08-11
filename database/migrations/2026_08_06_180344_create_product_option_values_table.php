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
        Schema::create('product_option_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_option_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('option_value_id');
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->unsignedBigInteger('sort_order')->default(1);
            $table->string('sku')->nullable();
            $table->boolean('stock_subtract')->default(false);
            $table->boolean('is_default')->default(false);

            $table->jsonb('name')->nullable()->default('{}');
            $table->jsonb('description')->nullable()->default('{}');
            $table->jsonb('images')->nullable()->default('{}');

            $table->timestamps();

            $table->unique(['product_option_id', 'option_value_id']);
            $table->index(['product_id', 'store_id', 'sort_order']);

            $table->foreign(['option_value_id', 'store_id'])
                ->references(['id', 'store_id'])
                ->on('option_values')
                ->cascadeOnDelete();

            $table->foreign(['product_option_id', 'product_id'], 'foreign_options_key')
                ->references(['id', 'product_id'])
                ->on('product_options')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_option_values');
    }
};
