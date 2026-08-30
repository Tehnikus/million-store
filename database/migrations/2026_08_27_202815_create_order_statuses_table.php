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
        Schema::create('order_statuses', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_paid')->default(false);
            $table->boolean('is_shipped')->default(false);
            $table->boolean('is_finished')->default(false);
            $table->jsonb('name');
            $table->tinyText('color');
            $table->tinyText('icon');
            $table->timestamps();
            $table->softDeletes();

            // $table->unique(['store_id', 'is_active', 'is_default', 'deleted_at'], 'default_order_status')->where('is_default = true')->where('deleted_at = false');
            $table->unique(['is_active', 'is_default', 'deleted_at'], 'default_order_status')->where('is_default = true')->where('deleted_at = false');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_statuses');
    }
};
