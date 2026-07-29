<?php

namespace App\Models\Global;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class StoreLanguage extends Model
{
    public $incrementing = true;

    protected $table = 'store_languages';

    protected $fillable = [
        'store_id',
        'language_id',
        'is_default',
        'is_active',
        'sort_order',
    ];

    // Reverse relations to store and language is used to create repeater with relationship() in store settings form
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    // Reverse relations to store and language is used to create repeater with relationship() in store settings form
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
