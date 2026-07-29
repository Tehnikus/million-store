<?php

namespace App\Models\Blog;

use App\Models\Global\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;
use App\Domain\Seo\HasSlugs;

class BlogAuthor extends Model
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
        'seo_keywords',
        'avatar',
        'social_links',
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
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'sort_order'    => 'integer',
        'avatar'        => 'array',
        'social_links'  => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function blogPosts(): HasMany
    {
         return $this->hasMany(BlogPost::class, 'author_id');
    }
}