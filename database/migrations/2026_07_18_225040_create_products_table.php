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
        // Table for product data that is shared across all stores
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->jsonb('global_name')->nullable()->default('{}'); // Translatable internal name, admin search only
            $table->string('sku')->unique()->nullable(); // Non-translatable article/SKU, used for orders, ERP import, barcodes
            $table->enum('units', ['pcs', 'volume', 'weight'])->default('pcs');
            $table->softDeletes();
            $table->timestamps();
        });

        DB::statement('
            CREATE UNIQUE INDEX product_unique
            ON products (id)
            WHERE deleted_at IS NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
