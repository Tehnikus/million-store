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
        
        $globalName       = $data['global_name']          ?? [];
        // $descriptionData  = $data['description']          ?? [];
        $categories       = $data['facet_categories']     ?? [];
        $manufacturers    = $data['facet_manufacturers']  ?? [];
        $tags             = $data['facet_tags']           ?? [];

        // Collect option facet values
        $optionSignatures = $data['optionSignatures'] ?? [];
        $optionFacets = collect($optionSignatures)
            ->flatMap(fn ($signature) => collect($signature['selectedOptions'] ?? []))
            ->filter(fn ($row) => filled($row['option_select'] ?? null) && filled($row['option_value_select'] ?? null))
            ->map(fn ($row) => [
                'facet_group_id' => (int) $row['option_select'],
                'facet_value_id' => (int) $row['option_value_select'],
            ])
            ->unique(fn ($row) => "{$row['facet_group_id']}-{$row['facet_value_id']}")
            ->values()
            ->all();

        $attributeFacets = collect($data['description']['attributes_description'] ?? [])
            ->flatMap(fn ($group) =>
                collect($group['attribute_values_description'] ?? [])
                    ->map(fn ($value) => [
                        'facet_group_id' => (int) $group['attribute_id'],
                        'facet_value_id' => (int) $value['attribute_value_id'],
                    ])
            )
            ->all();

        // app(SyncProductFacets::class)->handle($record, $store->id, FacetType::OptionValue, $rows);

        // Set primary category and manufacturer taking in account import routine
        if (!array_key_exists('primary_category_id', $data) && array_key_exists('facet_categories', $data)) {
            $data['description']['primary_category_id'] = collect($categories)->firstWhere('is_primary', true)['facet_value_id'] ?? null;
        }
        if (!array_key_exists('primary_manufacturer_id', $data) && array_key_exists('facet_manufacturers', $data)) {
            $data['description']['primary_manufacturer_id'] = collect($manufacturers)->firstWhere('is_primary', true)['facet_value_id'] ?? null;
        }

        // Unset data that does not belong to product description to avoid mass assignment errors is strict mode
        // TODO As statePath is used for descriptions data, this might be unnecessary
        unset(
            $data['global_name'], 
            // $data['description'],
            // $data['options_description'],
            $data['facet_categories'], 
            $data['facet_manufacturers'], 
            $data['facet_tags'],
            // $data['facet_options'],
            // $data['facet_attributes'],
        );


        // Update produt data in single transaction
        return DB::transaction(function () use ($data, $storeId, $globalName, $categories, $manufacturers, $tags, $optionFacets, $attributeFacets) {

            // Create/Edit product
            if (empty($data['product_id'])) {
                // Create product case
                $product = Product::create(['global_name' => $globalName]);
                // State path in product form for description tabs ->statePath('description')
                $description = ProductDescription::create([...$data['description'], 'product_id' => $product->id, 'store_id' => $storeId]);
            } else {
                // Edit product case
                $product = Product::find($data['product_id']);
                $product->update(['global_name' => $globalName]);
                $description = ProductDescription::where('product_id', $data['product_id'])->where('store_id', $storeId)->first();
                // State path in product form for description tabs ->statePath('description')
                $description->update($data['description']);
            }

            // Update facet_index
            // Update category facets
            app(SyncProductFacets::class)->handle(
                $product->id,
                $storeId,
                FacetType::Category,
                collect($categories)->map(fn($row) => [
                    'facet_group_id' => (int) $row['facet_group_id'],
                    'facet_value_id' => (int) $row['facet_value_id'],
                    'sort_order'     => (int) $row['sort_order'],
                ])->all()
            );

            // Update manufacturer facets
            app(SyncProductFacets::class)->handle(
                $product->id,
                $storeId,
                FacetType::Manufacturer,
                collect($manufacturers)->map(fn($row) => [
                    'facet_group_id' => (int) $row['facet_group_id'],
                    'facet_value_id' => (int) $row['facet_value_id'],
                    'sort_order'     => (int) $row['sort_order'],
                ])->all()
            );

            // Update tag facets
            app(SyncProductFacets::class)->handle(
                $product->id,
                $storeId,
                FacetType::Tag,
                collect($tags)->map(fn($row) => [
                    'facet_group_id' => (int) $row['facet_group_id'],
                    'facet_value_id' => (int) $row['facet_value_id'],
                    'sort_order'     => (int) $row['sort_order'],
                ])->all()
            );

            app(SyncProductFacets::class)->handle($product->id, $storeId, FacetType::OptionValue, $optionFacets);
            app(SyncProductFacets::class)->handle($product->id, $storeId, FacetType::AttributeValue, $attributeFacets);

            // Return model as expected
            return $product;
        });
    }
}