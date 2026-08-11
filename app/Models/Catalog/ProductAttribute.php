<?php

namespace App\Models\Catalog;

use App\Domain\Support\Concerns\InheritsColumnFromParent;
use App\Models\Global\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductAttribute extends Model
{
    public $incrementing = true;
    protected $table = 'product_attributes';

    protected $fillable = [
        'product_id',
        'attribute_id',
        'store_id',
        'sort_order',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'attribute_id' => 'integer',
        'store_id' => 'integer',
        'sort_order' => 'integer',
    ];

    public function productAttributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    use InheritsColumnFromParent;

    protected static function inheritedColumns(): array
    {
        return [
            'store_id' => ['attribute', 'store_id'],
        ];
    }
}
