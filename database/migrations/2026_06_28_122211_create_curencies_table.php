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
            // Displayed name
            $table->string('name');
            // ISO code for JSON-LD data
            $table->string('iso_code')->unique(true);
            // Displayed sign. May or may not be used to format price
            $table->string('sign');
            // Exchange rate compared to default currency
            $table->decimal('rate', 10, 6)->default(1);
            $table->integer('decimal_places')->default(2);
            // Default currency flag. Must be only one default currency system-wide. 
            $table->boolean('rate_default')->default(false);
            // Is currency enabled. Default currency cannot be disabled
            $table->boolean('is_active')->default(true);
            $table->timestamps();
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
