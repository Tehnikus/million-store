<?php

namespace App\Models\Catalog;

use App\Models\Global\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Option extends Model
{
    use HasTranslations;
    protected $fillable = [
        'store_id',
        'is_active',
        'show_in_facets',
        'sort_order',
        'name',
        'type'
    ];
    protected $casts = [
        'is_active'             => 'boolean',
        'show_in_facets'        => 'boolean',
        'sort_order'            => 'integer',
        'name'                  => 'array',
        'type'                  => 'string'
    ];
    protected $translatable = [
        'name'
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(OptionValue::class)->with('currentStoreSlugs');
    }
}
