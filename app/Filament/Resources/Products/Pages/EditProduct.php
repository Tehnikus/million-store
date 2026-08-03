<?php

namespace App\Filament\Resources\Products\Pages;

use App\Domain\Catalog\Actions\SyncProductFacets;
use App\Domain\Catalog\FacetType;
use App\Filament\Concerns\StripsFacetsFormState;
use App\Filament\Concerns\StripsSlugFormState;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Catalog\FacetIndex;
use App\Models\Catalog\ProductDescription;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;


class EditProduct extends EditRecord
{
    use StripsSlugFormState; 
    use StripsFacetsFormState;
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    // Fill form data before render
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $storeId = Filament::getTenant()->id;

        // Fill description fields
        $description = ProductDescription::query()
            ->where('product_id', $this->record->id)
            ->where('store_id', $storeId)
            ->first();

        $data['description'] = $description?->toArray() ?? [];

        // Fill facet data
        // Fill category facet data
        $data['facet_categories'] = $this->record->categoryFacets()
            ->where('store_id', $storeId)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (FacetIndex $facet) => ['facet_value_id' => $facet->facet_value_id, 'facet_group_id' => $facet->facet_group_id])
            ->values()
            ->all();

        // Fill manufacturer facet data
        $data['facet_manufacturers'] = $this->record->manufacturerFacets()
            ->where('store_id', $storeId)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (FacetIndex $facet) => ['facet_value_id' => $facet->facet_value_id, 'facet_group_id' => $facet->facet_group_id])
            ->values()
            ->all();

        // Fill tag facet data
        $data['facet_tags'] = $this->record->tagFacets()
            ->where('store_id', $storeId)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (FacetIndex $facet) => ['facet_value_id' => $facet->facet_value_id])
            ->values()
            ->all();

        return $data;
    }

    // Save product
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Store id is used only here to save data. Otherwise model does not know about store id
        $storeId = Filament::getTenant()->id;
        
        // Remove slugs from saved form data. Slugs are saved by the form itself
        $data = $this->stripSlugFormState($data);
        
        // Remove descriptions from data to save them separately
        $description  = Arr::pull($data, 'description', []);
        // Save descriptions
        ProductDescription::updateOrCreate(
            ['product_id' => $record->id, 'store_id' => Filament::getTenant()->id],
            $description,
        );

        // Update product category facets
        app(SyncProductFacets::class)->handle(
            $record,
            $storeId,
            FacetType::Category,
            collect($data['facet_categories'] ?? [])
                ->map(fn ($row, $i) => ['facet_value_id' => $row['facet_value_id'], 'facet_group_id' => $row['facet_group_id'],  'sort_order' => $i + 1])
                ->values()
                ->all(),
        );

        // Update manufacturer facets
        app(SyncProductFacets::class)->handle(
            $record,
            $storeId,
            FacetType::Manufacturer,
            collect($data['facet_manufacturers'] ?? [])
                ->map(fn ($row, $i) => ['facet_value_id' => $row['facet_value_id'], 'facet_group_id' => $row['facet_group_id'],  'sort_order' => $i + 1])
                ->values()
                ->all(),
        );

        // Update tag facets
        app(SyncProductFacets::class)->handle(
            $record,
            $storeId,
            FacetType::Tag,
            collect($data['facet_tags'] ?? [])
                ->map(fn ($row, $i) => ['facet_value_id' => $row['facet_value_id'],  'sort_order' => $i + 1])
                ->values()
                ->all(),
        );

        // Update product manufacturer facets
        // app(SyncProductFacets::class)->handle(
        //     $record,
        //     $storeId,
        //     FacetType::Manufacturer,
        //     $data['manufacturer_id'] ? [['facet_value_id' => $data['manufacturer_id']]] : [],
        // );

        // Remove facets from data after save
        $data = $this->stripFacetsFormState($data);

        $record->update($data);

        return $record;
    }

}
