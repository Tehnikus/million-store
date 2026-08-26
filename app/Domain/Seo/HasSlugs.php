<?php

namespace App\Domain\Seo;

use App\Models\Seo\Slug;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasSlugs
{
    public static function bootHasSlugs(): void
    {
        static::deleting(fn (self $model) => $model->slugs()->delete());
    }

    public function slugs(): MorphMany
    {
        return $this->morphMany(Slug::class, 'sluggable');
    }

    public function activeSlug(): MorphOne
    {
        return $this->morphOne(Slug::class, 'sluggable')->where('is_active', true);
    }
}