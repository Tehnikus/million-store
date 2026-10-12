<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\FacetType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class PurgeDeletedCatalogValue
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