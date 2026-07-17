<?php

namespace App\Filament\Concerns;

use App\Models\Slug;
use Filament\Facades\Filament;

trait SyncsSlugFields
{
    protected function pullSlugsFromData(array &$data): array
    {
        $slugs = $data['slugs'] ?? [];
        unset($data['slugs']);

        return $slugs;
    }

    protected function syncSlugs(array $slugs, string $sluggableType, int $sluggableId): void
    {
        $storeId = Filament::getTenant()->id;

        foreach ($slugs as $languageId => $slugValue) {
            if (blank($slugValue)) {
                continue;
            }

            Slug::updateOrCreate(
                [
                    'store_id'        => $storeId,
                    'language_id'     => $languageId,
                    'sluggable_type'  => $sluggableType,
                    'sluggable_id'    => $sluggableId,
                ],
                [
                    'slug'      => $slugValue,
                    'is_active' => true,
                ]
            );
        }
    }

    protected function loadSlugsIntoData(array $data, string $sluggableType, int $sluggableId): array
    {
        $data['slugs'] = Slug::query()
            ->where('sluggable_type', $sluggableType)
            ->where('sluggable_id', $sluggableId)
            ->where('is_active', true)
            ->pluck('slug', 'language_id')
            ->all();

        return $data;
    }
}