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
        // create_customer_groups_table
        Schema::create('customer_groups', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // 'guest', 'registered', 'wholesale'
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->jsonb('name')->default('{}'); // Translatable, spatie/laravel-translatable
            $table->decimal('price_modifier_percent', 5, 2)->default(0); // -15.00 = discount 15%, +20.00 = markup

            // User group flags
            $table->boolean('free_shipping')->default(false);
            $table->boolean('requires_approval')->default(false);   // Should be approved by admin
            $table->boolean('show_prices')->default(true);          // false for hidden prices
            $table->boolean('tax_exempt')->default(false);          // reverse charge taxes for B2B with VAT number (EU)

            $table->boolean('is_default')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->uniqueIndex('is_default')->where('is_default = true');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_groups');
    }
};
