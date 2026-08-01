<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
// use Filament\Facades\Filament;
use Spatie\Translatable\HasTranslations;

class ProductDescription extends Model
{
    use HasTranslations;

    protected $fillable = [
        'product_id',
        'store_id',
        'is_active',
        'is_available',
        'is_available_from',
        'is_available_to',
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
        'is_available'          => 'boolean',
        'is_available_from'     => 'datetime',
        'is_available_to'       => 'datetime',
        'sort_order'            => 'integer',
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
}
