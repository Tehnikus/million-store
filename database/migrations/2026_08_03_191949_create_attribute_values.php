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
        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained('attributes', 'id')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();

            // Flags
            $table->boolean('is_active')->default(false);
            $table->boolean('show_in_facets')->default(false);
            $table->integer('sort_order')->default(1);
            // Descriptions
            $table->jsonb('name')->nullable()->default('{}');
            $table->jsonb('description')->nullable()->default('{}');
            $table->jsonb('images')->nullable()->default('{}');
            $table->tinyText('robots')->default('noindex, nofollow');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_values');
    }
};
