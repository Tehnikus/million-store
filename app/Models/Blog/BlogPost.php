<?php

namespace App\Models\Blog;

use App\Models\Global\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;
use App\Domain\Seo\HasSlugs;
use App\Domain\Media\Concerns\HasProcessedImages;

class BlogPost extends Model
{
    use HasTranslations;
    use HasSlugs;
    use HasProcessedImages;
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
        'robots',
        'is_active',
        'blog_post_products',
        'blog_post_tags',
        'sort_order',
        'author_id'
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
        'author_id'             => 'integer',
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

    public function blogTags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'blog_post_tag')
            ->withTimestamps();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(BlogAuthor::class, 'author_id');
    }

    // Required for image conversions
    public function imageColumns(): array
    {
        return [
            'images' => [
                'type'        => 'blog', // Image type. Sets which dimensions to choose from StoreSettings and what directory to store images in
                'slug_source' => 'name', // Translatable field to take converted image names from. Will be slugged
            ],
        ];
    }

}
