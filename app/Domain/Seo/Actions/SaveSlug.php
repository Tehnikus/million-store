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
            $new = Slug::create([
                'store_id'       => $storeId,
                'language_id'    => $languageId,
                'slug'           => $slugValue,
                'sluggable_type' => $type,
                'sluggable_id'   => $id,
                'is_active'      => true,
                'robots'         => $robots ?? 'index, follow',
            ]);

            if ($existing) {
                // Collapse the whole redirect chain: every slug that pointed to $existing now points directly to $new.
                // Thanks to the fact that we do this on EVERY renaming, 
                // the chain never grows deeper than one level
                // it is enough to rebind only the direct “children” of $existing.
                Slug::where('sluggable_type', $type)
                    ->where('sluggable_id', $id)
                    ->where('store_id', $storeId)
                    ->where('language_id', $languageId)
                    ->where('redirected_to_id', $existing->id)
                    ->update(['redirected_to_id' => $new->id]);

                $existing->update([
                    'is_active'        => false,
                    'redirected_to_id' => $new->id,
                ]);
            }

            return $new;
        });
    }
}