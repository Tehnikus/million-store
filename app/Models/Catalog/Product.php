<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use SoftDeletes, HasTranslations;

    public $translatable = ['global_name'];

    protected $fillable = [
        'global_name',
        'sku',
        'units',
    ];

    protected $casts = [
        'global_name' => 'array',
    ];

    // Level 1: remove this product from ONE store only. Soft-deletes the
    // product_stores row for that store; the product itself, and its
    // presence in other stores, is untouched.
    public static function removeFromStore(int $productId, int $storeId): void
    {
        ProductStore::where('product_id', $productId)->where('store_id', $storeId)->delete();
    }

    // Level 2: remove this product everywhere. Soft-deletes the product
    // itself plus every product_stores row it has, across every store.
    public static function removeCompletely(int $productId): void
    {
        DB::transaction(function () use ($productId) {
            ProductStore::where('product_id', $productId)->delete();
            Product::where('id', $productId)->delete();
        });
    }
}