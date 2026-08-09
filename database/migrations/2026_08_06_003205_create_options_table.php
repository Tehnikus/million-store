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
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            // Flags
            $table->boolean('is_active')->default(false);
            $table->boolean('show_in_facets')->default(false);
            $table->unsignedBigInteger('sort_order')->default(1);
            // Descriptions
            $table->jsonb('name')->nullable()->default('{}');
            $table->tinyText('type')->default('radio');
            $table->timestamps();

            // Indexes
            $table->index(['store_id', 'is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('options');
    }
};
