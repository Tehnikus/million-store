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
        Schema::create('facet_page_index', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facet_page_id')->constrained('facet_pages', 'id')->cascadeOnDelete();
            $table->unsignedBigInteger('store_id');
            $table->unsignedTinyInteger('facet_type_id');       // 1=category, 2=manufacturer, 3=attribute, 4=tag, 5=option
            $table->unsignedBigInteger('facet_group_id');       // entity parent id, like parent category or option group parent 
            $table->unsignedBigInteger('facet_value_id');       // entity id 
            $table->timestamps();

            $table->index(['facet_value_id',  'facet_group_id', 'facet_type_id','store_id'], 'facet_page_lookup');
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
