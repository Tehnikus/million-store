<?php

namespace App\Filament\Resources\Products\Pages;

use App\Domain\Catalog\Actions\SyncProductFacets;
use App\Domain\Catalog\Actions\UpsertProduct;
use App\Domain\Catalog\FacetType;
use App\Filament\Concerns\StripsFacetsFormState;
use App\Filament\Resources\Products\ProductResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class CreateProduct extends CreateRecord
{
    use StripsFacetsFormState;
    protected static string $resource = ProductResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $store = Filament::getTenant();
        return app(UpsertProduct::class)->handle($data, $store->id);
    }

}
