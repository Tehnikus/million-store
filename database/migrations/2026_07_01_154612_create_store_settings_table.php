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
            $table->jsonb('checkout')->nullable()->default('{}');               // Minimal checkout amount, register/guest checkout, if customer agreement mandatory, invoice number format UA-{year}-{number}
            $table->jsonb('delivery')->nullable()->default('{}');               // Default delivery wages and time intervals
            $table->jsonb('legal')->nullable()->default('{}');                  // Legal pages selectors: customer agreement, return policy, etc
            $table->jsonb('taxes')->nullable()->default('{}');                  // Catalog display pricies with tax. Checkout display pricies with tax 
            $table->jsonb('analytics')->nullable()->default('{}');              // Google analytics ID, Tag manager ID, Meta Pixel ID etc.
            $table->jsonb('seo_defaults')->nullable()->default('{}');           // SEO defaults
            $table->jsonb('notifications')->nullable()->default('{}');          // E-Mail and other notifications
            $table->jsonb('ai_settings')->nullable()->default('{}');            // AI settings
            $table->jsonb('layouts')->nullable()->default('{}');                // Pages layouts
            $table->jsonb('menu')->nullable()->default('{}');                   // Main menu
            $table->jsonb('contacts')->nullable()->default('{}');               // Contacts
            $table->jsonb('homepage')->nullable()->default('{}');               // Homepage
            $table->jsonb('maintenance')->nullable()->default('{}');            // Put current store in maintenance mode
            $table->timestamps();

            // Indexes
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
