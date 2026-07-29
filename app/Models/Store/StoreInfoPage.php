<?php

namespace App\Models\Store;

use App\Models\Global\Store;
use App\Domain\Seo\HasSlugs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;


class StoreInfoPage extends Model
{
    use HasTranslations;
    use HasSlugs;
    
    protected $fillable = [
        'store_id',
        'name',
        'h1',
        'meta_title',
        'meta_description',
        'description_short',
        'description_full',
        'images',
        'seo_keywords',
        'faq',
        'how_to',
        'footer',
        'is_active',
        'sort_order',
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

    protected $casts = [
        'is_active'             => 'boolean',
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
        'blog_post_products'    => 'array',
        'blog_post_tags'        => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

}
