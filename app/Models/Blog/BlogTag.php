<?php

namespace App\Models\Blog;

use App\Domain\Media\Concerns\HasProcessedImages;
use App\Domain\Media\ImageType;
use App\Domain\Support\Concerns\InvalidatesNavigationBadges;
use App\Filament\Resources\BlogTags\BlogTagResource;
use App\Models\Global\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;
use App\Domain\Seo\HasSlugs;

class BlogTag extends Model
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
        'is_active',
        'is_menu',
        'sort_order',
        'robots',
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
        'is_menu'               => 'boolean',
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

    /**
     * Settings for image conversions
     * @return array{images: array{slug_source: string, type: string}}
     */
    public function imageColumns(): array
    {
        return [
            'images' => [
                'type' => ImageType::BlogTag, // Image type. Sets which dimensions to choose from StoreSettings and what directory to store images in
                'slug_source' => 'name',       // Translatable field to take converted image names from. Will be slugged
            ],
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function blogPosts(): BelongsToMany
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_tag')
            ->withPivot(['sort_order'])
            ->withTimestamps();
    }

    // Clear cache of admin navigation badges when model is changed
    use InvalidatesNavigationBadges;
    protected static function navigationBadgeResources(): array
    {
        return [BlogTagResource::class];
    }

}
