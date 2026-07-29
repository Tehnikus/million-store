<?php

namespace App\Filament\Resources\StoreInfoPages\Pages;

use App\Filament\Resources\StoreInfoPages\StoreInfoPageResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\StripsSlugFormState;

class CreateStoreInfoPage extends CreateRecord
{
    use StripsSlugFormState;
    protected static string $resource = StoreInfoPageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->stripSlugFormState($data);
    }
}
