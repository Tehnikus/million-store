<?php

namespace App\Models\Catalog;

use App\Models\Global\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    protected $fillable = ['product_price_tier_id', 'currency_id', 'price'];

    protected $casts = ['price' => 'decimal:2'];

    public function tier(): BelongsTo
    {
        return $this->belongsTo(ProductPriceTier::class, 'product_price_tier_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
