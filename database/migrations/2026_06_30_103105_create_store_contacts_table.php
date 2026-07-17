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
        // create_store_contacts_table
        Schema::create('store_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();

            // Translatable fields: HasTranslations, jsonb with language keys inside
            // e.g. {"uk": "...", "pl": "..."}

            // Organization / LocalBusiness JSON-LD
            $table->jsonb('legal_name');
            $table->jsonb('organization_description')->nullable();
            $table->jsonb('local_business_description')->nullable();

            // Address, used in JSON-LD microdata
            $table->jsonb('address_country')->nullable();
            $table->jsonb('address_region')->nullable();
            $table->jsonb('address_locality')->nullable();
            $table->jsonb('address_street')->nullable();
            $table->jsonb('country_iso')->nullable(); // ISO 3166-1 alpha-2, e.g. {"uk": "UA", "pl": "PL"}
            $table->jsonb('postal_code')->nullable();
            $table->jsonb('latitude')->nullable(); // free-text per language; no strict validation
            $table->jsonb('longitude')->nullable();
            $table->jsonb('open_hours')->nullable();

            // Contacts
            $table->jsonb('email')->nullable();
            $table->jsonb('phones')->default('{}');          // {"uk": ["+380..."], "pl": ["+48..."]}
            $table->jsonb('social_links')->default('{}');    // {"uk": {"facebook": "..."}, "pl": {...}}
            $table->jsonb('social_contacts')->default('{}'); // {"uk": {"whatsapp": "..."}, "pl": {...}}

            $table->timestamps();
            $table->unique('store_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_contacts');
    }
};
