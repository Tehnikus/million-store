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
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('product_reviews')->cascadeOnDelete(); // Self-reference for replies. Also allows to delete the whole review thread if parent is deleted
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->unsignedBigInteger('thread_id')->nullable()->after('parent_id');
            $table->string('locale'); // Review language
            $table->string('author_name');
            $table->string('author_email')->nullable();
            $table->text('body')->nullable();
            $table->unsignedTinyInteger('rating')->nullable(); // 1..5
            $table->boolean('is_admin_reply')->default(false);  // If true it's admin reply
            $table->boolean('is_approved')->default(false); // Don't show on frontend until approved
            $table->timestamps();

            // Indexes
            $table->index(['product_id', 'is_approved', 'locale']);
            $table->index('thread_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
