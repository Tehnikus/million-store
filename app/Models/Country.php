<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class Country extends Model
{
    use HasTranslations;

    protected $fillable = [
        'name',
        'iso_code',
        'phone_code',
        'default_currency_id',
        'is_eu_member',
        'regions',
        'is_active',
    ];
    protected $casts = [
        'name'          => 'array', // Cast name to array because it is translatable
        'regions'       => 'array', // Regions are made with repeater, thus also array
        'is_eu_member'  => 'boolean',
        'is_active'     => 'boolean',
    ];

    protected $translatable = ['name'];

    public function defaultCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'default_currency_id');
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'store_countries')
            ->using(StoreLanguage::class)
            ->withPivot(['is_default', 'is_active']);
    }
}
