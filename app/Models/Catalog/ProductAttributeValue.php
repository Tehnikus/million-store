<?php

namespace App\Models\Catalog;

use App\Domain\Support\Concerns\InheritsColumnFromParent;
use App\Models\Global\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class ProductAttributeValue extends Model
{
    protected $fillable = [
        'attribute_value_id',
        'product_id',
        'store_id',
        'sort_order',
        'name',
        'description',
        'images',
    ];
    protected $casts = [
        'attribute_value_id'            => 'integer',
        'product_id'                    => 'integer',
        'store_id'                      => 'integer',
        'sort_order'                    => 'integer',
        'name'                          => 'array',
        'description'                   => 'array',
        'images'                        => 'array',
    ];

    public function productAttribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'product_attribute_id');
    }

    use InheritsColumnFromParent;

    protected static function inheritedColumns(): array
    {
        return [
            'product_id' => ['productAttribute', 'product_id'],
            'store_id'   => ['productAttribute', 'store_id'],
        ];
    }
}
