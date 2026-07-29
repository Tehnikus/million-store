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

    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class);
    }
}