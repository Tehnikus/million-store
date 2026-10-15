<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\FacetType;
use App\Models\Catalog\Category;
use App\Models\Catalog\Manufacturer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

/**
 * Counts associated catalog entities and displays warning messages on entity delete
 */
class DeletionWarnings
{

    /**
     * Build warning message of affected records when facet related entity is deleted
     * Shows affected products and filter pages count
     * @param FacetType $facetType
     * @param array $ids
     * @param int $storeId
     * @param bool $isGroup
     * @return HtmlString
     */
    public static function affectedWarningMessage(FacetType $facetType, array $ids, int $storeId, bool $isGroup = false): HtmlString
    {
        $ns = 'admin.catalog.facets.notifications';
        $message = [];

        switch ($facetType) {

            case FacetType::Category:
                $ids = array_values(array_unique(array_map('intval', $ids)));
                if ($ids === []) {
                    break;
                }

                $parents = Category::parentMap($storeId); // id => parent_id

                // Nearest ancestor that survives the deletion (bulk may delete a parent together with its child)
                $survivor = function (int $id) use ($parents, $ids): ?int {
                    $parentId = $parents[$id] ?? null;
                    while ($parentId !== null && in_array($parentId, $ids, true)) {
                        $parentId = $parents[$parentId] ?? null;
                    }
                    return $parentId;
                };

                $primaryByCategory = DB::table('product_descriptions')
                    ->selectRaw('primary_category_id, COUNT(*) AS cnt')
                    ->where('store_id', $storeId)
                    ->whereIn('primary_category_id', $ids)
                    ->groupBy('primary_category_id')
                    ->pluck('cnt', 'primary_category_id');

                $moved = $lost = 0;
                foreach ($primaryByCategory as $categoryId => $count) {
                    if ($survivor((int) $categoryId) === null) {
                        $lost += (int) $count;
                    } else {
                        $moved += (int) $count;
                    }
                }

                $secondary = max(0, self::affectedProductCount($facetType, $ids, $storeId) - $moved - $lost);
                $rootPages = self::affectedFacetPageCount($facetType, $ids, $storeId, true);
                $secondaryPages = self::affectedFacetPageCount($facetType, $ids, $storeId, false);
                $children = count(array_filter(
                    $parents,
                    fn($parentId, $id) => in_array($parentId, $ids, true) && !in_array($id, $ids, true),
                    ARRAY_FILTER_USE_BOTH,
                ));
                $n = count($ids) > 1 ? 'multiple_categories' : 'single_category';

                $message = [
                    'lost' => $lost
                        ? trans_choice("{$ns}.delete_{$n}_primary_products_warning", $lost, ['products' => $lost]) . ' ' . __("{$ns}.product_no_primary_category")
                        : null,
                    'moved' => $moved
                        ? trans_choice("{$ns}.delete_{$n}_primary_products_warning", $moved, ['products' => $moved]) . ' ' . __("{$ns}.product_primary_to_parent")
                        : null,
                    'secondary' => $secondary
                        ? trans_choice("{$ns}.delete_{$n}_secondary_products_warning", $secondary, ['products' => $secondary])
                        : null,
                    'rootPages' => $rootPages
                        ? trans_choice("{$ns}.delete_{$n}_primary_filter_pages_warning", $rootPages, ['filterPages' => $rootPages]) . ' ' . __("{$ns}.facet_page_no_primary")
                        : null,
                    'secondaryPages' => $secondaryPages
                        ? trans_choice("{$ns}.delete_{$n}_secondary_filter_pages_warning", $secondaryPages, ['filterPages' => $secondaryPages]) . ' ' . __("{$ns}.facet_page_no_unique_combination")
                        : null,
                    'children' => $children
                        ? trans_choice("{$ns}.delete_{$n}_children_warning", $children, ['children' => $children])
                        : null,
                ];
                break;

            case FacetType::Manufacturer:
                $ids = array_values(array_unique(array_map('intval', $ids)));
                if ($ids === []) {
                    break;
                }

                $parents = Manufacturer::parentMap($storeId); // id => parent_id

                // Nearest ancestor that survives the deletion (bulk may delete a parent together with its child)
                $survivor = function (int $id) use ($parents, $ids): ?int {
                    $parentId = $parents[$id] ?? null;
                    while ($parentId !== null && in_array($parentId, $ids, true)) {
                        $parentId = $parents[$parentId] ?? null;
                    }
                    return $parentId;
                };

                $primaryByManufacturer = DB::table('product_descriptions')
                    ->selectRaw('primary_manufacturer_id, COUNT(*) AS cnt')
                    ->where('store_id', $storeId)
                    ->whereIn('primary_manufacturer_id', $ids)
                    ->groupBy('primary_manufacturer_id')
                    ->pluck('cnt', 'primary_manufacturer_id');

                $moved = $lost = 0;
                foreach ($primaryByManufacturer as $manufacturerId => $count) {
                    if ($survivor((int) $manufacturerId) === null) {
                        $lost += (int) $count;
                    } else {
                        $moved += (int) $count;
                    }
                }

                // Primary manufacturer is always attached too, so the rest are secondary
                $secondary = max(0, self::affectedProductCount($facetType, $ids, $storeId) - $moved - $lost);
                $rootPages = self::affectedFacetPageCount($facetType, $ids, $storeId, true);
                $secondaryPages = self::affectedFacetPageCount($facetType, $ids, $storeId, false);
                $children = count(array_filter(
                    $parents,
                    fn($parentId, $id) => in_array($parentId, $ids, true) && !in_array($id, $ids, true),
                    ARRAY_FILTER_USE_BOTH,
                ));
                $n = count($ids) > 1 ? 'multiple_manufacturers' : 'single_manufacturer';

                $message = [
                    'lost' => $lost
                        ? trans_choice("{$ns}.delete_{$n}_primary_products_warning", $lost, ['products' => $lost]) . ' ' . __("{$ns}.product_no_primary_manufacturer")
                        : null,
                    'moved' => $moved
                        ? trans_choice("{$ns}.delete_{$n}_primary_products_warning", $moved, ['products' => $moved]) . ' ' . __("{$ns}.product_primary_manufacturer_to_parent")
                        : null,
                    'secondary' => $secondary
                        ? trans_choice("{$ns}.delete_{$n}_secondary_products_warning", $secondary, ['products' => $secondary])
                        : null,
                    'rootPages' => $rootPages
                        ? trans_choice("{$ns}.delete_{$n}_primary_filter_pages_warning", $rootPages, ['filterPages' => $rootPages]) . ' ' . __("{$ns}.facet_page_no_primary")
                        : null,
                    'secondaryPages' => $secondaryPages
                        ? trans_choice("{$ns}.delete_{$n}_secondary_filter_pages_warning", $secondaryPages, ['filterPages' => $secondaryPages]) . ' ' . __("{$ns}.facet_page_no_unique_combination")
                        : null,
                    'children' => $children
                        ? trans_choice("{$ns}.delete_{$n}_children_warning", $children, ['children' => $children])
                        : null,
                ];
                break;

            case FacetType::Tag:
                $products = self::affectedProductCount($facetType, $ids, $storeId);
                $rootPages = self::affectedFacetPageCount($facetType, $ids, $storeId, true);  // tags as root: planned, counts 0 until then
                $secondaryPages = self::affectedFacetPageCount($facetType, $ids, $storeId, false);
                $n = count($ids) > 1 ? 'multiple_tag' : 'single_tag';

                $message = [
                    'products' => $products
                        ? trans_choice("{$ns}.delete_{$n}_products_warning", $products, ['products' => $products])
                        : null,
                    'rootPages' => $rootPages
                        ? trans_choice("{$ns}.delete_{$n}_primary_filter_pages_warning", $rootPages, ['filterPages' => $rootPages]) . ' ' . __("{$ns}.facet_page_no_primary")
                        : null,
                    'secondaryPages' => $secondaryPages
                        ? trans_choice("{$ns}.delete_{$n}_secondary_filter_pages_warning", $secondaryPages, ['filterPages' => $secondaryPages]) . ' ' . __("{$ns}.facet_page_no_unique_combination")
                        : null,
                ];
                break;

            case FacetType::OptionValue:
                $products = self::affectedProductCount($facetType, $ids, $storeId);
                $filterPages = self::affectedFacetPageCount($facetType, $ids, $storeId);
                $scope = $isGroup ? 'group' : 'value';

                $message = [
                    'products' => $products
                        ? trans_choice("{$ns}.delete_option_{$scope}_products_warning", $products, ['products' => $products]) . ' ' . __("{$ns}.product_no_options")
                        : null,
                    'filterPages' => $filterPages
                        ? trans_choice("{$ns}.delete_option_{$scope}_filter_pages_warning", $filterPages, ['filterPages' => $filterPages]) . ' ' . __("{$ns}.facet_page_no_unique_combination")
                        : null,
                ];
                break;

            case FacetType::AttributeValue:
                $products = self::affectedProductCount($facetType, $ids, $storeId);
                $filterPages = self::affectedFacetPageCount($facetType, $ids, $storeId);
                $scope = $isGroup ? 'group' : 'value';

                $message = [
                    'products' => $products
                        ? trans_choice("{$ns}.delete_attribute_{$scope}_products_warning", $products, ['products' => $products])
                        : null,
                    'filterPages' => $filterPages
                        ? trans_choice("{$ns}.delete_attribute_{$scope}_filter_pages_warning", $filterPages, ['filterPages' => $filterPages]) . ' ' . __("{$ns}.facet_page_no_unique_combination")
                        : null,
                ];
                break;
        }

        $message = array_filter($message);

        return new HtmlString($message
            ? implode('<br><br>', $message) // Composed message "This will affect X products and Y facet pages..."
            : __('filament-actions::modal.confirmation')); // Fallback standard message "Are you sure...?"
    }

    /**
     * For delete confirmations: how many products of the store use any of these values.
     * Call BEFORE deleting (facet_index rows still exist then).
     *
     * @param int[] $valueIds
     */
    public static function affectedProductCount(FacetType $type, array $valueIds, int $storeId): int
    {
        $valueIds = array_values(array_unique(array_map('intval', $valueIds)));

        if ($valueIds === []) {
            return 0;
        }

        $placeholders = implode(', ', array_fill(0, count($valueIds), '?'));

        return (int) DB::scalar(
            "SELECT COUNT(DISTINCT product_id) FROM facet_index
             WHERE store_id = ? AND facet_type_id = ? AND facet_value_id IN ({$placeholders})",
            [$storeId, $type->value, ...$valueIds],
        );
    }

    /**
     * For delete confirmations: how many facet pages of the store use any of these entities (active or not).
     *
     * @param int[] $entityIds
     */
    public static function affectedFacetPageCount(FacetType $type, array $entityIds, int $storeId, ?bool $isRoot = null): int
    {
        $entityIds = array_values(array_unique(array_map('intval', $entityIds)));

        if ($entityIds === []) {
            return 0;
        }

        $placeholders = implode(', ', array_fill(0, count($entityIds), '?'));
        $rootSql = $isRoot === null ? '' : ' AND is_root = ' . ($isRoot ? 'true' : 'false');

        return (int) DB::scalar(
            "SELECT COUNT(DISTINCT facet_page_id) FROM facet_page_index
         WHERE store_id = ? AND facet_type_id = ? AND facet_value_id IN ({$placeholders}){$rootSql}",
            [$storeId, $type->value, ...$entityIds],
        );
    }
}