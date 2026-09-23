<?php

namespace App\Models\Catalog;

use App\Models\Global\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class ProductDescription extends Model
{
    use HasTranslations;
    protected $with = ['product'];

    protected $fillable = [
        'product_id',
        'store_id',
        'primary_category_id',
        'primary_manufacturer_id',
        'is_active',
        'is_available',
        'date_available_from',
        'date_available_to',
        'sort_order',
        'name',
        'h1',
        'meta_title',
        'meta_description',
        'images',
        'description_short',
        'description_full',
        'options_description',
        'attributes_description',
        'seo_keywords',
        'faq',
        'how_to',
        'footer',
        'robots',
    ];

    protected $casts = [
        'is_active'                 => 'boolean',
        'is_available'              => 'boolean',
        'date_available_from'       => 'datetime:Y-m-d H:i:s',
        'date_available_to'         => 'datetime:Y-m-d H:i:s',
        'sort_order'                => 'integer',
        'parent_id'                 => 'integer',
        'manufacturer_id'           => 'integer',
        'name'                      => 'array',
        'h1'                        => 'array',
        'meta_title'                => 'array',
        'meta_description'          => 'array',
        'images'                    => 'array',
        'description_short'         => 'array',
        'description_full'          => 'array',
        'options_description'       => 'array',
        'attributes_description'    => 'array',
        'seo_keywords'              => 'array',
        'faq'                       => 'array',
        'how_to'                    => 'array',
        'footer'                    => 'array',
    ];
    protected $translatable = [
        'name',
        'h1',
        'meta_title',
        'meta_description',
        'description_short',
        'description_full',
        'seo_keywords',
        'faq',
        'how_to',
        'footer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    // Store scope relation
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
