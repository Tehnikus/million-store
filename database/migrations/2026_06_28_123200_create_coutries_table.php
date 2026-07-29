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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->jsonb('name');                              // Translatable jsonb, spatie/laravel-translatable
            $table->string('iso_code', 2)->unique();            // UA, PL - for JSON-LD addressCountry
            $table->string('phone_code')->nullable();           // +380, +48 - order checkout format
            $table->boolean('is_eu_member')->default(false);    // For some legal usage
            $table->jsonb('regions')->nullable();               // Regions in JSON
            $table->foreignId('default_currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
