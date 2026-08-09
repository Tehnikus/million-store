<?php

use Illuminate\Database\Migrations\Migration;
use Tpetry\PostgresqlEnhanced\Schema\Blueprint;
use Tpetry\PostgresqlEnhanced\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // create_currencies_table
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // Displayed name
            $table->string('iso_code')->unique(true);        // ISO code for JSON-LD data
            $table->string('sign');                          // Displayed sign. May or may not be used to format price
            $table->decimal('rate', 10, 6)->default(1);      // Exchange rate compared to default currency
            $table->integer('decimal_places')->default(2);   // Decimal places
            $table->boolean('rate_default')->default(false); // Default currency flag. Must be only one default currency system-wide. 
            $table->boolean('is_active')->default(true);     // Is currency enabled. Default currency cannot be disabled
            $table->timestamps();

            // Indexes
            $table->index('is_active');
            $table->uniqueIndex('rate_default')->where('rate_default = true');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
