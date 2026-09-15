<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\FacetType;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductDescription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UpsertProduct
{
    public function handle(array $data, int $storeId): Model {
        
        $globalName     = $data['global_name']          ?? [];
        $categories     = $data['facet_categories']     ?? [];
        $manufacturers  = $data['facet_manufacturers']  ?? [];
        $options        = $data['facet_options']        ?? [];
        $attributes     = $data['facet_attributes']     ?? [];

        // Set primary category and manufacturer taking in account import routine
        // Category: $data['primary_category_id'], then $data['facet_categories'][]['is_primary'], then null
        if (!array_key_exists('primary_category_id', $data) && array_key_exists('facet_categories', $data)) {
            $data['primary_category_id'] = collect($categories)->firstWhere('is_primary', true)['facet_value_id'] ?? null;
        }
        // Manufacturer: $data['primary_manufacturer_id'], then $data['facet_manufacturers'][]['is_primary'], then null
        if (!array_key_exists('primary_manufacturer_id', $data) && array_key_exists('facet_manufacturers', $data)) {
            $data['primary_manufacturer_id'] = collect($manufacturers)->firstWhere('is_primary', true)['facet_value_id'] ?? null;
        }

        // Unset data that does not belong to product description to avoid mass assignment errors is strict mode
        unset(
            $data['global_name'], 
            $data['facet_categories'], 
            $data['facet_manufacturers'], 
            $data['facet_tags'],
            $data['facet_options'],
            $data['facet_attributes'],
        );

        // Update produt data in single transaction
        return DB::transaction(function () use ($data, $storeId, $globalName, $categories, $manufacturers, $options, $attributes) {

            // Create/Edit product
            if (empty($data['product_id'])) {
                // Create product case
                $product = Product::create(['global_name' => $globalName]);
                $description = ProductDescription::create([...$data, 'product_id' => $product->id, 'store_id' => $storeId]);
            } else {
                // Edit product case
                $product = Product::find($data['product_id']);
                $product->update(['global_name' => $globalName]);
                $description = ProductDescription::where('product_id', $data['product_id'])->where('store_id', $storeId)->first();
                $description->update($data);
            }

            // Update facet_index
            // Update category facets
            app(SyncProductFacets::class)->handle(
                $data['product_id'],
                $storeId,
                FacetType::Category,
                collect($categories)->map(fn($row) => [
                    'facet_group_id' => (int) $row['facet_group_id'],
                    'facet_value_id' => (int) $row['facet_value_id'],
                ])->all()
            );

            // Update manufacturer facets
            app(SyncProductFacets::class)->handle(
                $data['product_id'],
                $storeId,
                FacetType::Manufacturer,
                collect($categories)->map(fn($row) => [
                    'facet_group_id' => (int) $row['facet_group_id'],
                    'facet_value_id' => (int) $row['facet_value_id'],
                ])->all()
            );

            // Update tag facets
            app(SyncProductFacets::class)->handle(
                $data['product_id'],
                $storeId,
                FacetType::Tag,
                collect($categories)->map(fn($row) => [
                    'facet_group_id' => (int) $row['facet_group_id'],
                    'facet_value_id' => (int) $row['facet_value_id'],
                ])->all()
            );

            // TODO Options and attributes may have special cases according to their structure
            // TODO update prices, inventories and option signatures

            // Return model as expected
            return $product->with(['descriptions']);
        });
    }
}