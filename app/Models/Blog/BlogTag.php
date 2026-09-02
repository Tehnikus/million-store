<?php

namespace App\Models\Blog;

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

}
