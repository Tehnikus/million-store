<?php

namespace App\Models\Global;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class StoreCurrency extends Model
{
    public $incrementing = true;

    protected $table = 'store_currencies';

    protected $fillable = [
        'store_id',
        'currency_id',
        'is_active',
        'sort_order',
    ];

    // Reverse relations to store and currency is used to create repeater with relationship() in store settings form
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    // Reverse relations to store and currency is used to create repeater with relationship() in store settings form
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
