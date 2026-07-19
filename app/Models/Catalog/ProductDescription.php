<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;
use App\Domain\Media\Concerns\HasProcessedImages;

class ProductDescription extends Model
{
    use HasTranslations, HasProcessedImages;

    public $translatable = [
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

    protected $fillable = [
        'product_store_id',
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
        'name' => 'array',
        'h1' => 'array',
        'meta_title' => 'array',
        'meta_description' => 'array',
        'images' => 'array',
        'description_short' => 'array',
        'description_full' => 'array',
        'seo_keywords' => 'array',
        'faq' => 'array',
        'how_to' => 'array',
        'footer' => 'array',
    ];

    // Required for image conversions
    public function imageColumns(): array
    {
        return [
            'images' => [
                'type'        => 'product', // Image type. Sets which dimensions to choose from StoreSetting and what directory to store images in
                'slug_source' => 'name', // Translatable field to take converted image names from. Will be slugged
            ],
        ];
    }

    public function productStore(): BelongsTo
    {
        return $this->belongsTo(ProductStore::class);
    }
}