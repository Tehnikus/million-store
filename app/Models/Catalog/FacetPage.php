<?php

namespace App\Models\Catalog;

use App\Domain\Media\Concerns\HasProcessedImages;
use App\Domain\Media\ImageType;
use App\Domain\Seo\HasSlugs;
use App\Domain\Support\Concerns\InvalidatesNavigationBadges;
use App\Filament\Resources\FacetPages\FacetPageResource;
use App\Models\Global\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class FacetPage extends Model
{
    use HasSlugs;
    use HasTranslations;
    use HasProcessedImages;
    
    protected $table = 'facet_pages';
    protected $fillable = [
        'store_id',
        'is_active',
        'sort_order',
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
        'images',
        'robots',
    ];
    protected $casts = [
        'store_id'          => 'integer',
        'is_active'         => 'boolean',
        'sort_order'        => 'integer',
        'name'              => 'array',
        'h1'                => 'array',
        'meta_title'        => 'array',
        'meta_description'  => 'array',
        'description_short' => 'array',
        'description_full'  => 'array',
        'seo_keywords'      => 'array',
        'faq'               => 'array',
        'how_to'            => 'array',
        'footer'            => 'array',
        'images'            => 'array',
        'robots'            => 'string',
    ];

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

    public function facetIndex(): HasMany
    {
        return $this->hasMany(FacetPageIndex::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function imageColumns(): array
    {
        return [
            'images' => [
                'type' => ImageType::FacetPage, // Image type. Sets which dimensions to choose from StoreSettings and what directory to store images in
                'slug_source' => 'name',        // Translatable field to take converted image names from. Will be slugged
            ],
        ];
    }


    // Clear cache of admin navigation badges when model is changed
    use InvalidatesNavigationBadges;
    protected static function navigationBadgeResources(): array
    {
        return [FacetPageResource::class];
    }
}
