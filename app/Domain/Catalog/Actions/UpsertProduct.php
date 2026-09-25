<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\FacetType;
use App\Models\Catalog\FacetIndex;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductDescription;
use App\Models\Catalog\ProductOption;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UpsertProduct
{
    public function handle(array $data, int $storeId): Model {        

        $categoryFacets = collect($data['facet_categories'] ?? [])->map(fn($row) => [
            'facet_type_id'  => FacetType::Category,
            'facet_group_id' => (int) $row['facet_group_id'],
            'facet_value_id' => (int) $row['facet_value_id'],
            'sort_order'     => (int) $row['sort_order'],
        ])->all();

        $manufacturerFacets = collect($data['facet_manufacturers'] ?? [])->map(fn($row) => [
            'facet_type_id'  => FacetType::Manufacturer,
            'facet_group_id' => (int) $row['facet_group_id'],
            'facet_value_id' => (int) $row['facet_value_id'],
            'sort_order'     => (int) $row['sort_order'],
        ])->all();

        $tagFacets = collect($data['facet_tags'] ?? [])->map(fn($row) => [
            'facet_type_id'  => FacetType::Tag,
            'facet_group_id' => (int) $row['facet_group_id'],
            'facet_value_id' => (int) $row['facet_value_id'],
            'sort_order'     => (int) $row['sort_order'],
        ])->all();

        $attributeFacets = collect($data['description']['attributes_description'] ?? [])->flatMap(fn ($group) =>
            collect($group['attribute_values_description'] ?? [])
                ->map(fn ($value) => [
                    'facet_type_id'  => FacetType::AttributeValue,
                    'facet_group_id' => (int) $group['attribute_id'],
                    'facet_value_id' => (int) $value['attribute_value_id'],
                ])
        )
        ->all();

        // Get options
        $options = $data['optionSignatures'] ?? [];
        // Collect option facet values
        $optionFacets = collect($options)
            ->flatMap(fn ($signature) => collect($signature['selectedOptions'] ?? []))
            ->filter(fn ($row) => filled($row['option_select'] ?? null) && filled($row['option_value_select'] ?? null))
            ->map(fn ($row) => [
                'facet_type_id'  => FacetType::OptionValue,
                'facet_group_id' => (int) $row['option_select'],
                'facet_value_id' => (int) $row['option_value_select'],
            ])
            ->unique(fn ($row) => "{$row['facet_group_id']}-{$row['facet_value_id']}")
            ->values()
            ->all();
        // Collect option signatures
        $optionSignatures = collect($options)
            ->map(fn ($signature) => collect($signature['selectedOptions'] ?? [])
                ->filter(fn ($row) => filled($row['option_select'] ?? null) && filled($row['option_value_select'] ?? null))
                ->mapWithKeys(fn ($row) => [(int) $row['option_select'] => (int) $row['option_value_select']])
                ->all())
            ->filter()
            ->unique(fn ($signature) => static::signatureKey($signature))
            ->values();

        // Set primary category and manufacturer taking in account import routine
        if (!array_key_exists('primary_category_id', $data) && array_key_exists('facet_categories', $data)) {
            $data['description']['primary_category_id'] = collect($data['facet_categories'])->firstWhere('is_primary', true)['facet_value_id'] ?? null;
        }
        if (!array_key_exists('primary_manufacturer_id', $data) && array_key_exists('facet_manufacturers', $data)) {
            $data['description']['primary_manufacturer_id'] = collect($data['facet_manufacturers'])->firstWhere('is_primary', true)['facet_value_id'] ?? null;
        }


        // Update produt data in single transaction
        return DB::transaction(function () use ($data, $storeId, $categoryFacets, $manufacturerFacets, $tagFacets, $optionFacets, $attributeFacets, $optionSignatures) {

            // Create/Edit product
            if (empty($data['product_id'])) {
                // Create product case
                $product = Product::create(['global_name' => $data['global_name']]);
                // State path in product form for description tabs ->statePath('description')
                $description = ProductDescription::create([...$data['description'], 'product_id' => $product->id, 'store_id' => $storeId]);
            } else {
                // Edit product case
                $product = Product::find($data['product_id']);
                $product->update(['global_name' => $data['global_name']]);
                $description = ProductDescription::updateOrCreate(
                    ['product_id' => $data['product_id'], 'store_id' => $storeId],
                    $data['description']
                );
                // State path in product form for description tabs ->statePath('description')
                // $description->updateOrCreate($data['description']);
            }

            // Update facet_index
            // FacetIndex::where('product_id', $product->id)
            //     ->where('store_id', $storeId)
            //     ->delete();
            // FacetIndex::insert(array_map(
            //     fn (array $row) => [
            //         'product_id'      => $product->id,
            //         'store_id'        => $storeId,
            //         'facet_type_id'   => $row['facet_type_id'],
            //         'facet_group_id'  => $row['facet_group_id'] ?? 0,
            //         'facet_value_id'  => $row['facet_value_id'],
            //         'sort_order'      => $row['sort_order'] ?? 1,
            //     ],
            //     [...$categoryFacets, ...$manufacturerFacets, ...$tagFacets, ...$optionFacets, ...$attributeFacets]
            // ));
            app(SyncProductFacets::class)->handle($product->id, $storeId, FacetType::Category,       $categoryFacets);
            app(SyncProductFacets::class)->handle($product->id, $storeId, FacetType::Manufacturer,   $manufacturerFacets);
            app(SyncProductFacets::class)->handle($product->id, $storeId, FacetType::Tag,            $tagFacets);
            app(SyncProductFacets::class)->handle($product->id, $storeId, FacetType::OptionValue,    $optionFacets);
            app(SyncProductFacets::class)->handle($product->id, $storeId, FacetType::AttributeValue, $attributeFacets);

            // Update product_options
            ProductOption::where('product_id', $product->id)
                ->where('store_id', $storeId)
                ->delete();

            $optionSignatures->each(fn ($signature) => ProductOption::create([
                'product_id'       => $product->id,
                'store_id'         => $storeId,
                'option_signature' => $signature,
            ]));

            // Return model as expected
            return $product;
        });
    }

    protected static function signatureKey(array $signature): string
    {
        ksort($signature);
        return collect($signature)->map(fn ($v, $k) => "{$k}-{$v}")->implode('_');
    }
}