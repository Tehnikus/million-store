<?php

namespace App\Models\Catalog;

use App\Domain\Support\Concerns\InheritsColumnFromParent;
use App\Models\Global\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductOption extends Model
{
    public $incrementing = true;
    protected $table = 'product_options';

    protected $fillable = [
        'product_id',
        'option_id',
        'store_id',
        'sort_order',
    ];

    protected $casts = [
        'product_id'  => 'integer',
        'option_id'   => 'integer',
        'store_id'    => 'integer',
        'sort_order'  => 'integer',
    ];

    public function productOptionValues(): HasMany
    {
        return $this->hasMany(ProductOptionValue::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function product(): BelongsTo
{
    return $this->belongsTo(Product::class);
}

    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class);
    }

    use InheritsColumnFromParent;

    protected static function inheritedColumns(): array
    {
        return [
            'store_id' => ['option', 'store_id'],
        ];
    }
}
