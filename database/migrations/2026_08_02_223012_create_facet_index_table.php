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
        Schema::create('facet_index', function (Blueprint $table) {
            $table->id(); // Surrogate primary key only for filament components like repeater and select
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('store_id');
            $table->unsignedTinyInteger('facet_type_id');                   // 1=category, 2=manufacturer, 3=attribute, 4=tag, 5=option
            $table->unsignedBigInteger('facet_group_id')->default(0);       // entity parent id, like parent category or option group parent 
            $table->unsignedBigInteger('facet_value_id');                   // entity id 
            $table->integer('sort_order')->default(1);                      // product sort order in particular facet combination

            $table->timestamps();

            $table->unique(['product_id', 'store_id', 'facet_type_id', 'facet_group_id', 'facet_value_id'], 'facet_natural_key');
            $table->index(['store_id', 'facet_type_id', 'facet_value_id', 'product_id'], 'facet_lookup');

            // Composite foreign key on product_descriptions
            $table->foreign(['product_id', 'store_id'])
                ->references(['product_id', 'store_id'])
                ->on('product_descriptions')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facet_index');
    }
};
