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
        Schema::create('product_stores', function (Blueprint $table) {
            // Surrogate PK: lets ProductDescription/ProductPrice reference a single
            // scalar column instead of a composite (product_id, store_id) pair.
            // Required because Eloquent has no native composite PK support.
            $table->id();

            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();

            $table->boolean('is_active')->default(false);
            $table->boolean('is_available')->default(false);
            $table->dateTime('is_available_from')->nullable();
            $table->dateTime('is_available_to')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Real integrity constraint: one product can only appear once per store.
            // This is the constraint product_descriptions/product_prices FKs rely on transitively.
            $table->unique(['product_id', 'store_id']);

        });
        // Partial unique index scoped to non-deleted rows — same pattern as the
        // customers table's [store_id, email]. Lets a product be re-added to a
        // store after it was previously removed, without colliding with the old
        // soft-deleted row.
        DB::statement('
            CREATE UNIQUE INDEX product_store_unique
            ON product_stores (product_id, store_id)
            WHERE deleted_at IS NULL
        ');

        // Catalog lookup for the storefront: "active + available products in store X",
        // scoped to non-deleted rows only — this is the query that will actually
        // run on every category/listing page, so it should skip trashed rows for free.
        DB::statement("
            CREATE INDEX product_stores_catalog_lookup
            ON product_stores (store_id, is_active, is_available, product_id)
            WHERE deleted_at IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_stores');
    }
};
