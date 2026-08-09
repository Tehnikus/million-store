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
        Schema::create('languages', function(Blueprint $table) {
            $table->id();
            $table->string('name');                         // Displayed name
            $table->string('iso_code')->unique(true);       // ISO code
            $table->string('locale');                       // Locale for translations
            $table->string('ts_config')->default('simple'); // Postgres dictionary for tsvector search
            $table->string('image')->nullable(true);        // Flag
            // Default currency for language is required because Google bot and other will get prices related to language: Ukrainian -> UAH, Polish -> PLN, etc.
            $table->foreignId('default_currency_id')->constrained('currencies')->cascadeOnDelete(); 
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
