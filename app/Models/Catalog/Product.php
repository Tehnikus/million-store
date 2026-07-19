<?php

namespace App\Models\Catalog;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\Catalog\ProductFactory> */
    use HasFactory, SoftDeletes, HasTranslations;
    // 'model' is translatable (admin-facing internal name/search field,
    // not a customer-facing description - see product_descriptions for that)
    public $translatable = ['model'];

    protected $fillable = [
        'model',
        'sku',
        'units',
    ];

    protected $casts = [
        'model' => 'array', // required by HasTranslations for jsonb columns
    ];

    protected static function booted(): void
    {
        static::deleting(function (Product $product) {
            if (! $product->isForceDeleting()) {
                // Cascade soft delete: archiving the product also removes it
                // from every store's catalog, keeping product_stores consistent
                // with the parent instead of leaving "still active" rows
                // dangling under an archived product.
                $product->stores()->each(fn (ProductStore $store) => $store->delete());
            }
        });

        static::restoring(function (Product $product) {
            // 'restoring' fires before deleted_at is nulled on the model,
            // so capture it here before it's gone.
            $deletedAt = $product->deleted_at;

            // Only restore stores removed in the SAME cascade delete as the
            // product (matching timestamp) - not stores an admin had already
            // removed independently before the product was archived.
            $product->stores()
                ->onlyTrashed()
                ->where('deleted_at', $deletedAt)
                ->each(fn (ProductStore $store) => $store->restore());
        });
    }

    public function stores(): HasMany
    {
        return $this->hasMany(ProductStore::class);
    }

    // Convenience accessor for a specific store's pivot-like row,
    // e.g. $product->storeRecord($tenant->id) inside Filament form hooks.
    public function storeRecord(int $storeId): ?ProductStore
    {
        return $this->stores->firstWhere('store_id', $storeId);
    }

    // Used for stats, TODO
    // public function prices(): HasManyThrough
    // {
    //     return $this->hasManyThrough(ProductPrice::class, ProductStore::class);
    // }

    // Get description for store
    // public function descriptionForStore(int $storeId): ?ProductDescription
    // {
    //     return $this->descriptions->firstWhere('store_id', $storeId);
    // }
}
