<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class StoreLanguage extends Pivot
{
    protected $table = 'store_languages';

    protected $fillable = [
        'store_id',
        'language_id',
        'is_default',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::saving(function (StoreLanguage $pivot) {
            $becomingDefault = $pivot->is_default && $pivot->isDirty('is_default');

            if ($becomingDefault) {
                static::where('store_id', $pivot->store_id)
                    ->where('id', '!=', $pivot->id)
                    ->update(['is_default' => false]);
            }
        });
    }
}