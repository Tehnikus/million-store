<?php

namespace App\Models\Catalog;

use App\Domain\Catalog\Concerns\HasFacetIndexCleanup;
use App\Domain\Catalog\FacetType;
use App\Domain\Media\Concerns\HasProcessedImages;
use App\Domain\Seo\HasSlugs;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class OptionValue extends Model
{
    use HasTranslations;
    use HasSlugs;
    // use HasProcessedImages;

    protected $fillable = [
        'store_id',
        'is_active',
        'is_default',
        'show_in_facets',
        'sort_order',
        'name',
        'description',
        'images',
        'robots',
    ];
    protected $casts = [
        'store_id'         => 'integer',
        'is_active'        => 'boolean',
        'is_default'       => 'boolean',
        'show_in_facets'   => 'boolean',
        'sort_order'       => 'integer',
        'name'             => 'array',
        'description'      => 'array',
        'images'           => 'array',
    ];
    protected $translatable = [
        'name',
        'description',
    ];

    // Cleanup facet index on delete
    use HasFacetIndexCleanup;
    public function facetType(): FacetType
    {
        return FacetType::Option;
    }

    // public function imageColumns(): array
    // {
    //     return [
    //         'images' => [
    //             'type'        => 'option',    // Image type. Sets which dimensions to choose from StoreSettings and what directory to store images in
    //             'slug_source' => 'name',        // Translatable field to take converted image names from. Will be slugged
    //         ],
    //     ];
    // }
}
