<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('sku');
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_inventories');
    }
};