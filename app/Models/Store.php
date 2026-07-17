<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Store extends Model
{
    //

    protected $fillable = [
        'name',
        'host',
        'is_active',
    ];


    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class, 'store_languages')
            ->using(StoreLanguage::class)
            ->withPivot(['is_default', 'is_active']);
    }

    public function currencies(): BelongsToMany
    {
        return $this->belongsToMany(Currency::class, 'store_currencies')
            ->using(StoreCurrency::class)
            ->withPivot(['is_active']);
    }

    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'store_countries')
            ->using(StoreCountry::class)
            ->withPivot(['is_active']);
    }
}
