<?php

namespace App\Filament\Resources\StoreInfoPages\Pages;

use App\Filament\Resources\StoreInfoPages\StoreInfoPageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStoreInfoPage extends EditRecord
{
    protected static string $resource = StoreInfoPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
