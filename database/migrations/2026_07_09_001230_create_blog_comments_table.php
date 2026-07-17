<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('blog_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_post_id')->constrained('blog_posts')->cascadeOnDelete(); // Cascade delete if post is deleted            
            $table->foreignId('parent_id')->nullable()->constrained('blog_comments')->cascadeOnDelete(); // Self-reference for replies. Also allows to delete the whole comment thread if parent is deleted
            $table->unsignedBigInteger('thread_id')->nullable()->after('parent_id');
            $table->string('locale'); // Comment language
            $table->string('author_name');
            $table->string('author_email')->nullable();
            $table->text('body')->nullable();
            $table->unsignedTinyInteger('rating')->nullable(); // 1..5
            $table->boolean('is_admin_reply')->default(false);  // If true it's admin reply
            $table->boolean('is_approved')->default(false); // Don't show on frontend until approved
            $table->timestamps();
            $table->index(['blog_post_id', 'is_approved']);
            $table->index('thread_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_comments');
    }
};
