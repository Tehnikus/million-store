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
        Schema::create('languages', function(Blueprint $table) {
            $table->id();
            // Displayed name
            $table->string('name');
            // ISO code. Not sure 
            $table->string('iso_code')->unique(true);
            // Locale for HTML lang="{{ locale }}" tag
            $table->string('locale'); 
            // Full language name in english, e.g. "english", "spanish" for fulltext search
            // Exapmle:
            // $language = Language::where('iso_code', $currentLocale)->first();
            // Product::whereFullText(['name', 'description'], $searchTerm, [
            //     'language' => $language->ts_config,
            // ])->get();
            $table->string('ts_config')->default('simple'); 
            // Flag
            $table->string('image')->nullable(true);
            // Default currency for language is required because Google bot and other will get prices related to language: Ukrainian -> UAH, Polish -> PLN, etc.
            $table->foreignId('default_currency_id') // Column name
                ->constrained('currencies') // Table name where constrained id is
                ->restrictOnDelete(); // What to do if constrained table entry is modified
            // Is language active
            $table->boolean('is_active')->default(true);
            $table->timestamps();
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
