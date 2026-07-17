<?php

namespace App\Filament\Resources\StoreInfoPages\Pages;

use App\Filament\Resources\StoreInfoPages\StoreInfoPageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStoreInfoPages extends ListRecords
{
    protected static string $resource = StoreInfoPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
