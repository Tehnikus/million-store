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
            $table->foreignId('option_value_id')->constrained('option_values')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete(); 

            $table->string('sku')->nullable();
            $table->boolean('stock_subtract')->nullable(); // null = не отслеживаем остаток для этой комбинации
            $table->boolean('is_default')->default(false); // override дефолта конкретно для этого товара

            // Descriptions - duplicate or override values from original option descriptions
            $table->jsonb('name')->nullable()->default('{}');
            $table->jsonb('description')->nullable()->default('{}');
            $table->jsonb('images')->nullable()->default('{}');

            $table->timestamps();

            // Одно значение опции не может быть добавлено к продукту дважды в рамках одной group-привязки
            $table->unique(['product_option_id', 'option_value_id']);
            $table->index(['product_id', 'store_id']);

            // Composite foreign key on product_options
            $table->foreign(['product_option_id', 'product_id'])
                ->references(['option_id', 'product_id'])
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
