<?php

namespace App\Models\Catalog;

use App\Domain\Support\Concerns\InheritsColumnFromParent;
use App\Models\Global\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    use InheritsColumnFromParent;

    protected $fillable = [
        'product_id',
        'product_price_tier_id',
        'currency_id',
        'option_signature',
        'price',
    ];

    protected $casts = [
        'option_signature' => 'array',
        'price'            => 'decimal:2',
    ];

    protected static function inheritedColumns(): array
    {
        return [
            'product_id' => ['tier', 'product_id'],
        ];
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(ProductPriceTier::class, 'product_price_tier_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}