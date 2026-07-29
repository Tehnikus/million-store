<?php

namespace App\Models\Global;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class StoreCountry extends Model
{
    public $incrementing = true;
    protected $table = 'store_countries';
    protected $fillable = [
        'store_id',
        'country_id',
        'is_active',
        'sort_order'
    ];

    // Reverse relations to store and country is used to create repeater with relationship() in store settings form
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    // Reverse relations to store and country is used to create repeater with relationship() in store settings form
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
