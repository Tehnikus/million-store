<?php

namespace App\Models\Catalog;

use App\Models\Global\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Attribute extends Model
{
    use HasTranslations;
    protected $fillable = [
        'store_id',
        'is_active',
        'show_in_facets',
        'sort_order',
        'name',
    ];
    protected $casts = [
        'is_active'             => 'boolean',
        'show_in_facets'        => 'boolean',
        'sort_order'            => 'integer',
        'name'                  => 'array',
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
        return $this->hasMany(AttributeValue::class)->with('currentStoreSlugs');
    }
}
