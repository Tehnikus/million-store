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
        Schema::create('product_blog_posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->foreignId('blog_post_id')->constrained('blog_posts')->cascadeOnDelete();
            $table->unsignedBigInteger('store_id');
            $table->unsignedInteger('product_sort_order')->nullable();
            $table->unsignedInteger('post_sort_order')->nullable();
            $table->timestamps();

            $table->foreign(['product_id', 'store_id'], 'product_blog_posts_product_fk')
                ->references(['product_id', 'store_id'])
                ->on('product_descriptions')
                ->cascadeOnDelete();

            $table->unique(['blog_post_id', 'product_id']);
            $table->index(['product_id', 'store_id', 'post_sort_order']);
            $table->index(['blog_post_id', 'store_id', 'product_sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_blog_posts');
    }
};
