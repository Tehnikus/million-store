<?php

use Illuminate\Database\Migrations\Migration;
use Tpetry\PostgresqlEnhanced\Schema\Blueprint;
use Tpetry\PostgresqlEnhanced\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_price_tier_id')->constrained('product_price_tiers')->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->jsonb('option_signature')->nullable();
            $table->decimal('price', 10, 2);
            $table->timestamps();

            $table->index(['product_id', 'currency_id']);
            $table->uniqueIndex(['product_price_tier_id', 'currency_id', 'option_signature'])->nullsNotDistinct();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};