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
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->jsonb('image_dimensions')->nullable()->default('{}');       // Image dimensions
            $table->jsonb('checkout_settings')->nullable()->default('{}');      // Minimal checkout amount, register/guest checkout, if customer agreement mandatory, invoice number format UA-{year}-{number}
            $table->jsonb('delivery_settings')->nullable()->default('{}');      // Default delivery wages and time intervals
            $table->jsonb('legal_settings')->nullable()->default('{}');         // Legal pages selectors: customer agreement, return policy, etc
            $table->jsonb('tax_settings')->nullable()->default('{}');           // Catalog display pricies with tax. Checkout display pricies with tax 
            $table->jsonb('analytics_settings')->nullable()->default('{}');     // Google analytics ID, Tag manager ID, Meta Pixel ID etc.
            $table->jsonb('seo_defaults')->nullable()->default('{}');           // SEO defaults
            $table->jsonb('notification_settings')->nullable()->default('{}');  // E-Mail and other notifications
            $table->jsonb('maintenance_settings')->nullable()->default('{}');   // Put current store in maintenance mode
            $table->timestamps();
            $table->unique('store_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
