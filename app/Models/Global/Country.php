<?php

namespace App\Models\Global;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'name'          => 'array',
        'regions'       => 'array',
        'is_eu_member'  => 'boolean',
        'is_active'     => 'boolean',
    ];
    protected $translatable = ['name'];

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(
            Store::class,         // Model
            'store_countries',    // Table name
            'country_id',         // Column of this model (Country) in pivot table
            'store_id'            // Column of related model (Store) in pivot table
        )->withPivot('is_active');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'default_currency_id', 'id');
    }
}
