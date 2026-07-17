<?php

namespace App\Domain\Seo;

use App\Models\Slug;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

trait ChecksSlugUniqueness
{
    protected static function slugIsTaken(string $slug, int $languageId, ?\Closure $excludeUsing = null): bool
    {
        return Slug::query()
            ->where('store_id', Filament::getTenant()->id)
            ->where('language_id', $languageId)
            ->where('slug', $slug)
            ->when($excludeUsing, fn(Builder $query) => $excludeUsing($query))
            ->exists();
    }

    protected static function validateSlugLive(
        $livewire,
        string $path,
        ?string $state,
        string $languageId,
        ?\Closure $excludeUsing,
    ): void {
        $livewire->resetErrorBag($path);

        if (blank($state)) {
            return;
        }

        $validator = \Illuminate\Support\Facades\Validator::make(
            ['slug' => $state],
            ['slug' => 'alpha_dash:ascii'],
            ['slug.alpha_dash' => __('admin.seo.slugs.errors.alpha_dash')],
        );

        if ($validator->fails()) {
            $livewire->addError($path, $validator->errors()->first('slug'));
            return;
        }

        if (self::slugIsTaken($state, $languageId, $excludeUsing)) {
            $livewire->addError($path, __('admin.seo.slugs.errors.slug_taken'));
        }
    }
}