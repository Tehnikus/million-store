<?php

namespace App\Models\Catalog;


use App\Domain\Seo\HasSlugs;
use App\Models\Store;
use App\Models\Slug;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;


/**
 * @property int $product_id
 * @property int $store_id
 * @property bool $is_active
 * @property bool $is_available
 * @property \Illuminate\Support\Carbon|null $is_available_from
 * @property \Illuminate\Support\Carbon|null $is_available_to
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class ProductStore extends Model
{
    /** @use HasFactory<\Database\Factories\Catalog\ProductStoreFactory> */
    use SoftDeletes, HasSlugs, HasFactory;

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

    public function description(): HasOne
    {
        return $this->hasOne(ProductDescription::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function priceFor(int $currencyId): ?ProductPrice
    {
        return $this->prices->firstWhere('currency_id', $currencyId);
    }


    // Page visibility rule: does the product page exist on the frontend at all? 
    // Independent of whether it can be purchased right now a page can stay visible (pre-order, "coming soon", "sold out")
    // While add-to-cart is disabled.
    public function scopeVisible($query)
    {
        return $query->where('is_active', true);
    }

    // Purchase rule: can this product be added to the cart right now?
    // Deliberately separate from page visibility (is_active) - checks
    // is_available plus the pre-order/limited-offer date window.
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

    public function slugs(): MorphMany
    {
        return $this->morphMany(Slug::class, 'sluggable');
    }

    public function activeSlug(): MorphOne
    {
        return $this->morphOne(Slug::class, 'sluggable')->where('is_active', true);
    }

}
