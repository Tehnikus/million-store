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
