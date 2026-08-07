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
        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('option_id')->constrained('options')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete(); // Required for frontend requests omitting JOINs
            $table->integer('sort_order')->default(1);
            $table->timestamps();

            $table->unique(['product_id', 'option_id']); // unique index to fit composite foreign key in product_option_values
            $table->index(['product_id', 'store_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_options');
    }
};
