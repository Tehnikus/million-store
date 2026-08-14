<?php

namespace App\Models\Catalog;

use App\Models\Customer\CustomerGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class ProductPriceTier extends Model
{
    use HasTranslations;

    protected $fillable = [
        'product_id', 'store_id', 'customer_group_id',
        'name', 'is_discount', 'priority',
        'valid_from', 'valid_until', 'valid_quantity',
    ];

    protected $casts = [
        'name'           => 'array',
        'is_discount'    => 'boolean',
        'priority'       => 'integer',
        'valid_from'     => 'datetime',
        'valid_until'    => 'datetime',
        'valid_quantity' => 'integer',
    ];

    public $translatable = ['name'];

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function productDescription(): BelongsTo
    {
        return $this->belongsTo(ProductDescription::class, 'product_id', 'product_id');
    }

    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class);
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