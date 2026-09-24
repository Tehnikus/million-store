<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductInventory extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'quantity',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'quantity'   => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}