<?php

namespace App\Models\Catalog;

use App\Domain\Media\Concerns\HasProcessedImages;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class ProductDescription extends Model
{
    use HasTranslations, HasProcessedImages;

    public $incrementing = false; // Disables Eloquent's auto-increment handling on insert (it would otherwise try to read back a non-existent "id" column after INSERT).

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
        'product_id',
        'store_id',
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
        'name'              => 'array',
        'h1'                => 'array',
        'meta_title'        => 'array',
        'meta_description'  => 'array',
        'images'            => 'array',
        'description_short' => 'array',
        'description_full'  => 'array',
        'seo_keywords'      => 'array',
        'faq'               => 'array',
        'how_to'            => 'array',
        'footer'            => 'array',
    ];

    // Required for image conversions
    public function imageColumns(): array
    {
        return [
            'images' => [
                'type' => 'product',
                'slug_source' => 'name',
            ],
        ];
    }
}