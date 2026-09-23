<?php

namespace App\Models\Catalog;

use App\Models\Customer\CustomerGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductPriceTier extends Model
{
    protected $fillable = [
        'id',
        'product_id',
        'store_id',
        'customer_group_id',
        'name',
        'is_discount',
        'is_base',
        'priority',
        'date_valid_from',
        'date_valid_until',
        'valid_quantity',
    ];

    protected $casts = [
        'name'              => 'array',
        'is_discount'       => 'boolean',
        'is_base'           => 'boolean',
        'priority'          => 'integer',
        'date_valid_from'   => 'datetime:Y-m-d H:i:s',
        'date_valid_until'  => 'datetime:Y-m-d H:i:s',
        'valid_quantity'    => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class);
    }
}