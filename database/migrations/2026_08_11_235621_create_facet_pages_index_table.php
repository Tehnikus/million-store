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
        Schema::create('facet_page_index', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facet_page_id')->constrained('facet_pages', 'id')->cascadeOnDelete();
            $table->unsignedBigInteger('store_id');
            $table->unsignedTinyInteger('facet_type_id'); // 1=category, 2=manufacturer, 3=attribute, 4=tag, 5=option
            $table->unsignedBigInteger('facet_group_id'); // entity parent id, like parent category or option group parent 
            $table->unsignedBigInteger('facet_value_id'); // entity id 
            $table->boolean('is_root')->default(false);
            $table->timestamps();

            // Restrict facet_page_id to have only one root
            $table->uniqueIndex(['facet_page_id'], 'facet_page_index_one_root')->where('is_root = true'); 

            // Frontend first CTE: get all facet pages of this root
            $table->index(['store_id', 'facet_type_id', 'facet_group_id', 'facet_value_id'], 'facet_page_root_lookup')->where('is_root = true');

            // Frontend second CTE (HAVING COUNT) and admin panel duplicate check: exact match of all facets on this page
            $table->index(['facet_value_id', 'facet_group_id', 'facet_type_id', 'store_id'], 'facet_page_lookup');

            // Get all facets of a specific page (form, table)
            $table->index(['facet_page_id', 'store_id'], 'facet_page_index_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facet_page_index');
    }
};
