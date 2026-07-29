<?php

use Illuminate\Database\Migrations\Migration;
use Tpetry\PostgresqlEnhanced\Schema\Blueprint;
use Tpetry\PostgresqlEnhanced\Support\Facades\Schema;

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
            $table->timestamps();
            $table->index(['sku']);
            // $table->index(['global_name '])->algorithm('gin'); // TODO Make admin product search to support gin search by loacale and name, read about jsonb_path_ops
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
