<?php

namespace App\Domain\Catalog\Concerns;

use App\Domain\Catalog\FacetType;
use App\Models\Catalog\FacetIndex;

/**
 * Use on any model that participates in facet_index as a referenced entity
 * (Category, Manufacturer, Attribute, Tag).
 * The model must declare facetType(): FacetType.
 * On delete, removes all facet_index rows pointing to this record
 * facet_index has no FK to the referenced table (polymorphic via facet_type_id),
 * so this cleanup replaces what a real FK constraint would normally do.
 */
trait HasFacetIndexCleanup
{
    abstract public function facetType(): FacetType;

    public static function bootHasFacetIndexCleanup(): void
    {
        static::deleted(function (self $model) {
            FacetIndex::where('facet_type_id', $model->facetType()->value)
                ->where('facet_value_id', $model->getKey())
                ->delete();
        });
    }
}