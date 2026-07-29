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
        Schema::create('slugs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->string('slug');
            $table->nullableMorphs('sluggable'); // Polymorph reference to entity: BlogTag, BlogPost, Product, Category, Filter Seo Pages (TODO)
            $table->foreignId('redirected_to_id')->nullable()->constrained('slugs')->nullOnDelete(); // For 301 redirects. Old slug references to to new actual row of the same sluggable_type
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['store_id', 'language_id', 'slug']);
            $table->index(['sluggable_type', 'sluggable_id', 'store_id', 'language_id'], 'slugs_entity_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slugs');
    }
};
