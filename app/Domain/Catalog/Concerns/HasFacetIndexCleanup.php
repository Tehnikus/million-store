<?php

namespace App\Domain\Catalog\Concerns;

use App\Domain\Catalog\Actions\PurgeDeletedCatalogValue;
use App\Domain\Catalog\FacetType;
use App\Models\Catalog\FacetIndex;

/**
 * Use on any model that participates in facet_index as a referenced entity
 * (Category, Manufacturer, Tag, AttributeValue, OptionValue).
 * The model must declare facetType(): FacetType and have a store_id column.
 * On delete:
 *  - removes all facet_index rows pointing to this record
 *    (facet_index has no FK to the referenced table, polymorphic via facet_type_id,
 *    so this cleanup replaces what a real FK constraint would normally do);
 *  - deactivates facet pages built on this record and removes it from their combinations
 *    (see PurgeDeletedCatalogValue::facetPages()).
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

            // CLeanup filter pages on facet delete. Removes facets from facet_page_index and sets affected filter to is_active = false mode
            // Thus the admin has to decide what to do with affected filter pages - delete or modify the set of facets linked to the page  
            app(PurgeDeletedCatalogValue::class)->facetPages($model->facetType(), (int) $model->getKey(), (int) $model->store_id);
        });
    }
}