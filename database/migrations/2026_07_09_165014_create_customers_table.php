<?php

use Illuminate\Database\Migrations\Migration;
use Tpetry\PostgresqlEnhanced\Schema\Blueprint;
use Tpetry\PostgresqlEnhanced\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('customer_group_id')->nullable()->constrained('customer_groups')->restrictOnDelete();
            $table->string('locale'); // Customer preferred language to sent notifications in
            $table->string('email')->nullable();
            $table->jsonb('addresses')->default('[]'); // Array customer addresses
            $table->jsonb('contacts')->default('[]'); // Array of various contacts 
            $table->jsonb('wishlist')->default('[]'); // Whishlisted products
            $table->string('password');
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('company_name')->nullable();
            $table->string('vat_number')->nullable();
            $table->timestamp('gdpr_consent_at')->nullable();
            $table->boolean('marketing_opt_in')->default(false);
            $table->boolean('is_approved')->default(true);
            $table->boolean('is_anonymized')->default(false);
            $table->timestamp('anonymized_at')->nullable(); // Flag of executed user's deleteon request
            $table->softDeletes();
            $table->timestamps();

            $table->index(['store_id', 'is_approved', 'is_anonymized'], 'admin_count_customers'); // Count approved customers in admin
            // $table->uniqueIndex(['store_id', 'email'])->where('deleted_at is null');
        });

        // Create function to concat customer addresses in string separate fulltext search index in admin
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION customer_search_text(addresses jsonb, first_name text, last_name text, phone text, email text, vat_number text, company_name text) RETURNS text AS $$
                SELECT string_agg(DISTINCT word, ' ')
                FROM (
                    SELECT unnest(string_to_array(
                        coalesce(first_name, '') || ' ' ||
                        coalesce(last_name, '') || ' ' ||
                        coalesce(phone, '') || ' ' ||
                        coalesce(email, '') || ' ' ||
                        coalesce(vat_number, '') || ' ' ||
                        coalesce(company_name, ''),
                        ' '
                    )) AS word
                    UNION ALL
                    SELECT unnest(string_to_array(
                        coalesce(elem->>'city', '') || ' ' ||
                        coalesce(elem->>'street', '') || ' ' ||
                        coalesce(elem->>'country_iso', '') || ' ' ||
                        coalesce(elem->>'phone', '') || ' ' ||
                        coalesce(elem->>'recipient_name', ''),
                        ' '
                    ))
                    FROM jsonb_array_elements(addresses) elem
                ) words
                WHERE word <> ''
            $$ LANGUAGE sql IMMUTABLE;
        SQL);

        // The column to store indexed address
        DB::statement(<<<'SQL'
            ALTER TABLE customers
            ADD COLUMN addresses_search_text text
            GENERATED ALWAYS AS (customer_search_text(addresses, first_name, last_name, phone, email, vat_number, company_name)) STORED
        SQL);

        // Create index for partial search: 
        // "Kyi" will find Kyiv, 
        // "Kra" will find Krakow
        DB::statement(
            'CREATE INDEX customers_addr_search_trgm ON customers USING GIN (addresses_search_text gin_trgm_ops)'
        );

        // Index for containment search:
        // E.g. all customers in "PL" country
        DB::statement(
            'CREATE INDEX customers_addresses_gin ON customers USING GIN (addresses jsonb_path_ops)'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
        DB::statement('DROP FUNCTION IF EXISTS customer_search_text(jsonb, text, text, text, text, text, text)');
    }
};
