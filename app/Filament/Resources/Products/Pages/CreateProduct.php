<?php

namespace App\Filament\Resources\Products\Pages;

use App\Domain\Catalog\Actions\UpsertProduct;
use App\Filament\Resources\Products\ProductResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $store = Filament::getTenant();
        return app(UpsertProduct::class)->handle($data, $store->id);
    }

}
