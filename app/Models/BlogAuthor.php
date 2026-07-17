<?php

namespace App\Models;

use App\Domain\Seo\HasSlugs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

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

    // URL slugs
    public function slugs(): MorphMany
    {
        return $this->morphMany(Slug::class, 'sluggable');
    }

    public function activeSlug(): MorphOne
    {
        return $this->morphOne(Slug::class, 'sluggable')->where('is_active', true);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function blogPosts(): HasMany
    {
         return $this->hasMany(BlogPost::class, 'author_id');
    }
}