<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Derived table: one row per (product, store, language).
     * Fully rebuildable - never store manual data here (see product_descriptions.search_custom_terms / search_excluded_refs).
     */
    public function up(): void
    {
        Schema::create('product_search_index', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('store_id');
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();

            // Weighted source texts assembled in PHP (SearchIndexer). The DB only indexes them.
            $table->text('text_a')->default('');    // A: product name
            $table->text('text_b')->default('');    // B: category, manufacturer, custom search terms
            $table->text('text_c')->default('');    // C: attributes and options
            $table->text('text_d')->default('');    // D: tags

            // Derived snapshot of entities that took part in this row: {"category": [5], "option_value": [31, 32], ...}
            // Used for reverse lookup (entity changed -> which rows to rebuild)
            $table->jsonb('search_included_refs')->default('{}');

            $table->timestamps();

            // One row per product + store + language
            $table->unique(['product_id', 'store_id', 'language_id'], 'product_search_natural_key');

            // Frontend search is always scoped by store + language
            $table->index(['store_id', 'language_id'], 'product_search_scope');

            // Composite foreign key on product_descriptions (product_id + store_id is unique there)
            $table->foreign(['product_id', 'store_id'])
                ->references(['product_id', 'store_id'])
                ->on('product_descriptions')
                ->onDelete('cascade');
        });

        // Text search configuration. Stored as regconfig (not string): a generated column requires an
        // IMMUTABLE expression, and to_tsvector(regconfig, text) is immutable only when the config
        // comes from a regconfig-typed column, while text::regconfig is merely STABLE.
        // Copied from languages.ts_config by the indexer.
        DB::statement("ALTER TABLE product_search_index ADD COLUMN ts_config regconfig NOT NULL DEFAULT 'simple'::regconfig");

        // Weighted tsvector computed by Postgres. Changing ts_config or any text_* recomputes it automatically.
        DB::statement("
            ALTER TABLE product_search_index ADD COLUMN search_vector tsvector GENERATED ALWAYS AS (
                setweight(to_tsvector(ts_config, text_a), 'A') ||
                setweight(to_tsvector(ts_config, text_b), 'B') ||
                setweight(to_tsvector(ts_config, text_c), 'C') ||
                setweight(to_tsvector(ts_config, text_d), 'D')
            ) STORED
        ");

        // Full-text search
        DB::statement('CREATE INDEX product_search_vector_gin ON product_search_index USING GIN (search_vector)');

        // Reverse lookup by containment: search_included_refs @> '{"category": [5]}'
        DB::statement('CREATE INDEX product_search_refs_gin ON product_search_index USING GIN (search_included_refs jsonb_path_ops)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_search_index');
    }
};