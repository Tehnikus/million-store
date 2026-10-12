<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\FacetType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class PurgeDeletedCatalogValue
{

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
                    'pages' => $rootPages
                        ? trans_choice("{$ns}.delete_{$n}_primary_filter_pages_warning", $rootPages, ['filterPages' => $rootPages]) . ' ' . __("{$ns}.facet_page_no_primary")
                        : null,
                    'children' => $children
                        ? trans_choice("{$ns}.delete_{$n}_children_warning", $children, ['children' => $children])
                        : null,
                ];
                break;

            case FacetType::Manufacturer:
            case FacetType::Tag:
                // TODO
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
}