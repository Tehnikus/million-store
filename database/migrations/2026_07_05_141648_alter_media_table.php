<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            // Translatable image alt field: {"uk": "...", "pl": "...", "en": "..."}
            $table->jsonb('alt')->nullable()->after('custom_properties');
            $table->foreignId('store_id')->after('model_id')->constrained('stores')->cascadeOnDelete();
            $table->index(['model_type', 'model_id', 'collection_name', 'store_id'], 'media_store_lookup');
        });
    }

    public function shouldRun(): bool
    {
        return false;
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn('alt');
        });
    }
};
