<?php

namespace App\Models\Catalog;

use App\Models\Global\Store;
// use App\Models\Catalog\ProductManufacturers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;
use App\Domain\Seo\HasSlugs;
use App\Domain\Media\Concerns\HasProcessedImages;

class Manufacturer extends Model
{
    use HasTranslations;
    use HasSlugs;
    use HasProcessedImages;
    protected $fillable = [
        'store_id',
        'is_active',
        'parent_id',
        'show_in_facets',
        'sort_order',
        'name',
        'h1',
        'meta_title',
        'meta_description',
        'images',
        'description_short',
        'description_full',
        'seo_keywords',
        'faq',
        'how_to',
        'footer',
    ];

    protected $casts = [
        'is_active'             => 'boolean',
        'sort_order'            => 'integer',
        'parent_id'             => 'integer',
        'name'                  => 'array',
        'h1'                    => 'array',
        'meta_title'            => 'array',
        'meta_description'      => 'array',
        'images'                => 'array',
        'description_short'     => 'array',
        'description_full'      => 'array',
        'seo_keywords'          => 'array',
        'faq'                   => 'array',
        'how_to'                => 'array',
        'footer'                => 'array',
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

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    // public function products(): BelongsToMany
    // {
    //     return $this->belongsToMany(ProductManufacturers::class, 'product_categories',  'product_id', 'category_id');
    // }

    public function parentId(): BelongsTo
    {
        // Pass the class itself and the explicit foreign key column
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get the immediate child records for this record.
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function imageColumns(): array
    {
        return [
            'images' => [
                'type'        => 'manufacturer', // Image type. Sets which dimensions to choose from StoreSettings and what directory to store images in
                'slug_source' => 'name', // Translatable field to take converted image names from. Will be slugged
            ],
        ];
    }
}
