<?php

namespace App\Models\Catalog;

use App\Models\Slug;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @property int $product_id
 * @property int $store_id
 * @property bool $is_active
 * @property bool $is_available
 * @property Carbon|null $is_available_from
 * @property Carbon|null $is_available_to
 */
class ProductStore extends Model
{
    protected $fillable = [
        'product_id',
        'store_id',
        'is_active',
        'is_available',
        'is_available_from',
        'is_available_to',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_available' => 'boolean',
        'is_available_from' => 'datetime',
        'is_available_to' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    // localKey: 'product_id' - not a single-row-unique key on its own
    // (one product can have several product_stores rows, one per store),
    // so the extra ->where('store_id', ...) below is what actually
    // narrows this down to the one correct row. See the discussion on
    // why plain morphMany can't express a composite local key directly.
    public function slugs(): MorphMany
    {
        return $this->morphMany(Slug::class, 'sluggable', localKey: 'product_id')
            ->where('store_id', $this->store_id);
    }

    public function activeSlug(): MorphOne
    {
        return $this->morphOne(Slug::class, 'sluggable', localKey: 'product_id')
            ->where('store_id', $this->store_id)
            ->where('is_active', true);
    }

    public function canBeAddedToCart(): bool
    {
        if (! $this->is_available) {
            return false;
        }

        if ($this->is_available_from && $this->is_available_from->isFuture()) {
            return false;
        }

        if ($this->is_available_to && $this->is_available_to->isPast()) {
            return false;
        }

        return true;
    }
}