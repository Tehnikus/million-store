<?php

namespace App\Domain\Seo;

use App\Models\Seo\Slug;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasSlugs
{
    public static function bootHasSlugs(): void
    {
        // Block body on purpose: "deleting" is a halting event, any non-null return value
        // (an arrow fn would return the deleted row count) stops all listeners registered after this one
        static::deleting(function (self $model): void {
            $model->slugs()->delete();
        });
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