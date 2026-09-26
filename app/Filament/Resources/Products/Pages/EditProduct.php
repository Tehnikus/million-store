<?php

namespace App\Filament\Resources\Products\Pages;

use App\Domain\Catalog\Actions\UpsertProduct;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Catalog\ProductDescription;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;


class EditProduct extends EditRecord
{

    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $store  = Filament::getTenant();
        $record = $this->getRecord();

        $data['product_id'] = $record->id;

        // Fill description fields
        $description = ProductDescription::query()
            ->where('product_id', $this->record->id)
            ->where('store_id', $store->id)
            ->first();

        $data['description'] = $description?->toArray() ?? [];

        // Product model relation Product->categoryFacets()
        $data['facet_categories'] = $record->categoryFacets()
            ->where('store_id', $store->id)
            ->get()
            ->map(fn ($facet) => [
                'facet_value_id' => $facet->facet_value_id,
                'facet_group_id' => $facet->facet_group_id,
                'sort_order'     => $facet->sort_order,
                'is_primary'     => $facet->facet_value_id == $description?->primary_category_id
            ])->all();

        // Product model relation Product->manufacturerFacets()
        $data['facet_manufacturers'] = $record->manufacturerFacets()
            ->where('store_id', $store->id)
            ->get()
            ->map(fn ($facet) => [
                'facet_value_id' => $facet->facet_value_id,
                'facet_group_id' => $facet->facet_group_id,
                'sort_order'     => $facet->sort_order,
                'is_primary'     => $facet->facet_value_id == $description?->primary_manufacturer_id
            ])->all();

        // Product model relation Product->tagFacets()
        $data['facet_tags'] = $record->tagFacets()
            ->where('store_id', $store->id)
            ->get()
            ->map(fn ($facet) => [
                'facet_value_id' => $facet->facet_value_id,
                'facet_group_id' => $facet->facet_group_id,
                'sort_order'     => $facet->sort_order,
            ])->all();

        // Product model relation Product->options()
        $data['optionSignatures'] = $record->options()
            ->where('store_id', $store->id)
            ->get()
            ->map(fn ($option) => [
                'selectedOptions' => collect($option->option_signature)
                    ->map(fn ($valueId, $groupId) => [
                        'option_select'       => (string) $groupId,
                        'option_value_select' => (string) $valueId,
                    ])->values()->all(),
            ])->values()->all();

        $priceTiers = $record->priceTiers()
            ->where('store_id', $store->id)
            ->with('prices')
            ->get();

        $data['priceTiers'] = $priceTiers->isNotEmpty()
            ? $priceTiers->map(function ($tier) {
                $priceGrid = [];
                foreach ($tier->prices as $price) {
                    $comboKey = $price->option_signature ? UpsertProduct::signatureKey($price->option_signature) : 'base';
                    $priceGrid[$comboKey][$price->currency_id] = (string) $price->price;
                }

                return [
                    'customer_group_id' => $tier->customer_group_id,
                    'is_base'           => $tier->is_base,
                    'is_discount'       => $tier->is_discount,
                    'priority'          => $tier->priority,
                    'date_valid_from'   => $tier->date_valid_from,
                    'date_valid_until'  => $tier->date_valid_until,
                    'valid_quantity'    => $tier->valid_quantity,
                    'price'             => $priceGrid,
                ];
            })->all()
            : [[
                // Set empty base price tier on product edit in the neighboring store, when product is not linked yet
                'customer_group_id' => null,
                'is_base'            => true,
                'is_discount'        => false,
                'priority'           => 1,
                'date_valid_from'    => null,
                'date_valid_until'   => null,
                'valid_quantity'     => null,
                'price'              => [],
            ]];

        return $data;
    }

    // Save product
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Store id is used only here to save data. Otherwise model does not know about store id
        $store = Filament::getTenant();
        $data['product_id'] = $record->id ?? null;
        return app(UpsertProduct::class)->handle($data, $store->id);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    // public function hasCombinedRelationManagerTabsWithContent(): bool
    // {
    //     return true;
    // }
}
