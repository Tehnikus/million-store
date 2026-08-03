<?php

namespace App\Domain\Seo;

use App\Models\Seo\Slug;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Filament\Facades\Filament;

/**
 * Use when resource has URL slugs
 * Sets interaction with Slugs model and table
 */
trait HasSlugs
{
    public static function bootHasSlugs(): void
    {
        static::deleting(function (self $model) {
            $model->slugs()->delete();
        });
    }

    // Used when model record is deleted
    public function slugs(): MorphMany
    {
        return $this->morphMany(Slug::class, 'sluggable');
    }

    // Used in app\Filament\Schemas\Tabs\DescriptionTab.php
    public function currentStoreSlugs(): MorphMany
    {
        $tenantId = Filament::getTenant()?->id;
        return $this->morphMany(Slug::class, 'sluggable')->where('store_id', $tenantId);
    }
}