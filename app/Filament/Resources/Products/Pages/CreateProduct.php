<?php

namespace App\Filament\Resources\Products\Pages;

use App\Domain\Catalog\Actions\SyncProductFacets;
use App\Domain\Catalog\FacetType;
use App\Filament\Concerns\StripsFacetsFormState;
use App\Filament\Resources\Products\ProductResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use App\Filament\Concerns\StripsSlugFormState;

class CreateProduct extends CreateRecord
{
    use StripsSlugFormState;
    use StripsFacetsFormState;
    protected static string $resource = ProductResource::class;

    // protected function handleRecordCreation(array $data): Model
    // {
    //     // Get store id model admin form des not know about store id. The only one place 
    //     $storeId = Filament::getTenant()->id;
    //     $descriptionData = Arr::pull($data, 'description', []);
    //     $descriptionData = $this->stripSlugFormState($descriptionData);
    //     $product = static::getModel()::create($data);

    //     $product->descriptions()->create([
    //         ...$descriptionData,
    //         'store_id' => Filament::getTenant()->id,
    //     ]);

    //     return $product;
    // }

    protected function handleRecordCreation(array $data): Model
    {
        $storeId = Filament::getTenant()->id;

        // Collect facet data before any save 
        $categoryRows = collect($data['facet_categories'] ?? [])
            ->values()
            ->map(fn (array $row, int $index) => [
                'facet_value_id' => $row['facet_value_id'],
                'facet_group_id' => $row['facet_group_id'] ?? 0,
                'sort_order'     => $index + 1,
            ])
            ->all();

        $manufacturerRows = collect($data['facet_manufacturers'] ?? [])
            ->values()
            ->map(fn (array $row, int $index) => [
                'facet_value_id' => $row['facet_value_id'],
                'facet_group_id' => $row['facet_group_id'] ?? 0,
                'sort_order'     => $index + 1,
            ])
            ->all();

        // Remove facet data from form state before any other save process
        $data = $this->stripFacetsFormState($data);

        // Pull product description data from form state before product save
        $descriptionData = Arr::pull($data, 'description', []);

        // Create product in products table
        $product = static::getModel()::create($data);
        // Save product descriptions in product_descriptions table
        $product->descriptions()->create([
            ...$descriptionData,
            'store_id' => $storeId,
        ]);

        // Save facet data very last because it requires $product->id
        app(SyncProductFacets::class)->handle(
            $product,
            $storeId,
            FacetType::Category,
            $categoryRows,
        );
        app(SyncProductFacets::class)->handle(
            $product,
            $storeId,
            FacetType::Manufacturer,
            $manufacturerRows,
        );

        return $product;
    }

}
