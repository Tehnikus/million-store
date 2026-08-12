<?php

namespace App\Models\Global;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    // protected $with = ['countries', 'currencies', 'languages'];
    protected $fillable = [
        'name',
        'host',
        'is_active',
    ];

    // Store form relations
    public function storeLanguages(): HasMany
    {
        return $this->hasMany(StoreLanguage::class);
    }
    public function storeCurrencies(): HasMany
    {
        return $this->hasMany(StoreCurrency::class);
    }
    public function storeCountries(): HasMany
    {
        return $this->hasMany(StoreCountry::class);
    }


    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class, 'store_languages')->withPivot(['is_default', 'is_active']);
    }

    public function currencies(): BelongsToMany
    {
        return $this->belongsToMany(Currency::class, 'store_currencies')->withPivot('is_active');
    }

    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'store_countries')->withPivot('is_active');
    }
}
