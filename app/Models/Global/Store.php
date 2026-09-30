<?php

namespace App\Models\Global;

use App\Models\Store\StoreSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class Store extends Model
{
    // protected $with = ['storeSettings', 'storeLanguages', 'storeCurrencies', 'storeCountries'];
    protected $fillable = [
        'name',
        'host',
        'is_active',
    ];

    // Store form relations
    public function storeSettings(): HasOne
    {
        return $this->hasOne(StoreSettings::class, 'store_id', 'id');
    }
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

    public function activeLanguages(): Collection
    {
        return $this->languages->filter(fn ($language) => $language->pivot->is_active);
    }
    
    public function activeCurrencies(): Collection
    {
        return $this->currencies->filter(fn ($currency) => $currency->pivot->is_active);
    }

    public function activeCountries(): Collection
    {
        return $this->countries->filter(fn ($country) => $country->pivot->is_active);
    }
}
