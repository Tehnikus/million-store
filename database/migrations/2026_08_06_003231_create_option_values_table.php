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
        Schema::create('option_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('option_id');
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();

            $table->boolean('is_active')->default(false);
            $table->boolean('show_in_facets')->default(false);
            $table->boolean('is_default')->default(false);
            $table->unsignedBigInteger('sort_order')->default(1);
            $table->jsonb('name')->nullable()->default('{}');
            $table->jsonb('description')->nullable()->default('{}');
            $table->jsonb('images')->nullable()->default('{}');
            $table->tinyText('robots')->default('noindex, nofollow');
            $table->timestamps();

            $table->unique(['id', 'store_id']); // To match foreign constrain on для product_option_values

            $table->index(['option_id', 'store_id', 'is_active', 'sort_order']);

            $table->foreign(['option_id', 'store_id'])
                ->references(['id', 'store_id'])
                ->on('options')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('option_values');
    }
};
