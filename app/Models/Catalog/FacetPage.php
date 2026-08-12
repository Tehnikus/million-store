<?php

namespace App\Models\Catalog;

use App\Domain\Seo\HasSlugs;
use App\Models\Global\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class FacetPage extends Model
{
    use HasSlugs;
    use HasTranslations;
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
}
