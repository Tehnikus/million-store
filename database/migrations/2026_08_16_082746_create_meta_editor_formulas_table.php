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
        Schema::create('meta_editor_formulas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores', 'id')->cascadeOnDelete();
            $table->string('entity_type');
            $table->string('target_field');
            $table->string('locale');
            $table->boolean('is_active')->default(false);
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->text('formula');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meta_editor_formulas');
    }
};
