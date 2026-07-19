<?php

namespace App\Models\Catalog;

use App\Models\Currency;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $product_store_id
 * @property int $currency_id
 * @property string|null $price
 * @property string|null $special_price
 * @property Carbon|null $special_valid_from
 * @property Carbon|null $special_valid_until
 * @property int|null $special_valid_quantity
 */
class ProductPrice extends Model
{
    protected $fillable = [
        'product_store_id',
        'currency_id',
        'price',
        'special_price',
        'special_valid_from',
        'special_valid_until',
        'special_valid_quantity',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'special_price' => 'decimal:2',
        'special_valid_from' => 'datetime',
        'special_valid_until' => 'datetime',
        'special_valid_quantity' => 'integer',
    ];

    public function productStore(): BelongsTo
    {
        return $this->belongsTo(ProductStore::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function hasActiveSpecialPrice(int $quantity = 1): bool
    {
        if ($this->special_price === null) {
            return false;
        }

        if ($this->special_valid_quantity !== null && $quantity < $this->special_valid_quantity) {
            return false;
        }

        if ($this->special_valid_from && $this->special_valid_from->isFuture()) {
            return false;
        }

        if ($this->special_valid_until && $this->special_valid_until->isPast()) {
            return false;
        }

        return true;
    }

    public function currentPrice(int $quantity = 1): string|null
    {
        return $this->hasActiveSpecialPrice($quantity)
            ? $this->special_price
            : $this->price;
    }
}