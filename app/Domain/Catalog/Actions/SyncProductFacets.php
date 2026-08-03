<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\FacetType;
use App\Models\Catalog\FacetIndex;
use App\Models\Catalog\Product;
use Illuminate\Support\Facades\DB;

/**
 * Rebuilds facet_index rows for a single (product, store, facet type) scope.
 * Delete + insert pattern (see architecture plan section 6.3) safe because
 * facet_index for navigation types (category/manufacturer/attribute/tag)
 * has no other consumer of its own state besides this rebuild.
 *
 * @param array<int, array{facet_group_id?: int|null, facet_value_id: int, sort_order?: int}> $rows
 */
class SyncProductFacets
{
    public function handle(Product $product, int $storeId, FacetType $type, array $rows): void
    {
        DB::transaction(function () use ($product, $storeId, $type, $rows) {
            FacetIndex::where('product_id', $product->id)
                ->where('store_id', $storeId)
                ->where('facet_type_id', $type->value)
                ->delete();

            if ($rows === []) {
                return;
            }

            $now = now();

            FacetIndex::insert(array_map(
                fn (array $row) => [
                    'product_id'      => $product->id,
                    'store_id'        => $storeId,
                    'facet_type_id'   => $type->value,
                    'facet_group_id'  => $row['facet_group_id'] ?? 0,
                    'facet_value_id'  => $row['facet_value_id'],
                    'sort_order'      => $row['sort_order'] ?? 1,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ],
                $rows
            ));
        });
    }
}