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

        $data['facet_categories'] = $record->categoryFacets()
            ->where('store_id', $store->id)
            ->get()
            ->map(fn ($facet) => [
                'facet_value_id' => $facet->facet_value_id,
                'facet_group_id' => $facet->facet_group_id,
                'is_primary'     => $facet->facet_value_id == $description?->primary_category_id
            ])->all();

        $data['facet_manufacturers'] = $record->manufacturerFacets()
            ->where('store_id', $store->id)
            ->get()
            ->map(fn ($facet) => [
                'facet_value_id' => $facet->facet_value_id,
                'facet_group_id' => $facet->facet_group_id,
                'is_primary'     => $facet->facet_value_id == $description?->primary_manufacturer_id
            ])->all();

        $data['facet_tags'] = $record->tagFacets()
            ->where('store_id', $store->id)
            ->get()
            ->map(fn ($facet) => [
                'facet_value_id' => $facet->facet_value_id,
                'facet_group_id' => $facet->facet_group_id,
            ])->all();

        return $data;
    }


    // Fill form data before render
    // protected function mutateFormDataBeforeFill(array $data): array
    // {
    //     $storeId = Filament::getTenant()->id;
    //     // Fill facet data
    //     // Fill category facet data
    //     $data['facet_categories'] = $this->record->categoryFacets()
    //         ->where('store_id', $storeId)
    //         ->orderBy('sort_order')
    //         ->get()
    //         ->map(fn (FacetIndex $facet) => ['facet_value_id' => $facet->facet_value_id, 'facet_group_id' => $facet->facet_group_id])
    //         ->values()
    //         ->all();

    //     // Fill manufacturer facet data
    //     $data['facet_manufacturers'] = $this->record->manufacturerFacets()
    //         ->where('store_id', $storeId)
    //         ->orderBy('sort_order')
    //         ->get()
    //         ->map(fn (FacetIndex $facet) => ['facet_value_id' => $facet->facet_value_id, 'facet_group_id' => $facet->facet_group_id])
    //         ->values()
    //         ->all();

    //     // Fill tag facet data
    //     $data['facet_tags'] = $this->record->tagFacets()
    //         ->where('store_id', $storeId)
    //         ->orderBy('sort_order')
    //         ->get()
    //         ->map(fn (FacetIndex $facet) => ['facet_value_id' => $facet->facet_value_id])
    //         ->values()
    //         ->all();

    //     return $data;
    // }

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
    
}
