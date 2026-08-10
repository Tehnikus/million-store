<?php

namespace App\Models\Catalog;

use App\Domain\Support\Concerns\InheritsColumnFromParent;
use App\Models\Global\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class ProductOptionValue extends Model
{
    use HasTranslations;
    protected $fillable = [
        'product_option_id',
        'option_value_id',
        'product_id',
        'store_id',
        'sort_order',
        'sku',
        'stock_subtract',
        'is_default',
        'name',
        'description',
        'images',
    ];
    protected $casts = [
        'product_option_id' => 'integer',
        'option_value_id'   => 'integer',
        'product_id'        => 'integer',
        'store_id'          => 'integer',
        'sort_order'        => 'integer',
        'sku'               => 'string',
        'stock_subtract'    => 'boolean',
        'is_default'        => 'boolean',
        'name'              => 'array',
        'description'       => 'array',
        'images'            => 'array',
    ];

    public $translatable = [
        'name',
        'description',
    ];

    public function optionGroup(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class, 'product_option_id');
    }

    public function optionValue(): BelongsTo
    {
        return $this->belongsTo(OptionValue::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductOptionValuePrice::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    use InheritsColumnFromParent;

    protected static function inheritedColumns(): array
    {
        return [
            'product_id' => ['optionGroup', 'product_id'],
            'store_id'   => ['optionGroup', 'store_id'],
        ];
    }

}
