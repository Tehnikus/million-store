<?php

namespace App\Models\Catalog;

// use App\Domain\Support\Concerns\InheritsColumnFromParent;
use App\Models\Catalog\ProductOptionValue;
use App\Models\Global\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOptionValuePrice extends Model
{
    protected $fillable = [
        'product_option_value_id',
        'currency_id',
        'price_modifier',
    ];

    protected $casts = ['price_modifier' => 'decimal:2'];

    public function productOptionValue(): BelongsTo
    {
        return $this->belongsTo(ProductOptionValue::class, 'product_price_tier_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    // use InheritsColumnFromParent;

    // protected static function inheritedColumns(): array
    // {
    //     return [
    //         'product_id' => ['productOption', 'product_id'],
    //         'store_id'   => ['productOption', 'store_id'],
    //     ];
    // }
}
