<?php

namespace App\Domain\Seo\Actions;

use App\Models\Seo\Slug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SaveSlug
{
    public function handle(
        Model $sluggable,
        int $storeId,
        int $languageId,
        ?string $slugValue,
        ?string $robots = null,
    ): ?Slug {
        $type = $sluggable->getMorphClass();
        $id   = $sluggable->getKey();

        $existing = Slug::query()
            ->where('sluggable_type', $type)
            ->where('sluggable_id', $id)
            ->where('store_id', $storeId)
            ->where('language_id', $languageId)
            ->where('is_active', true)
            ->first();

        if (blank($slugValue)) {
            $existing?->delete();
            return null;
        }

        // If slug is not changed, only update robots tag
        if ($existing && $existing->slug === $slugValue) {
            if ($robots !== null && $existing->robots !== $robots) {
                $existing->update(['robots' => $robots]);
            }
            return $existing;
        }

        return DB::transaction(function () use ($existing, $type, $id, $storeId, $languageId, $slugValue, $robots) {
            
            // Create new slug in any case
            $new = Slug::create([
                'store_id'       => $storeId,
                'language_id'    => $languageId,
                'slug'           => $slugValue,
                'sluggable_type' => $type,
                'sluggable_id'   => $id,
                'is_active'      => true,
                'robots'         => $robots ?? 'index, follow',
            ]);

            // If previous slug is changed keep previous slug but set redirect to new one
            $existing?->update([
                'is_active'        => true, // If slug is not active it will response with 404, so keep it active explicitly
                'redirected_to_id' => $new->id,
            ]);

            return $new;
        });
    }
}