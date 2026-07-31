<?php

namespace App\Filament\Resources\Manufacturers\Pages;

use App\Filament\Resources\Manufacturers\ManufacturerResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\StripsSlugFormState;

class CreateManufacturer extends CreateRecord
{
    protected static string $resource = ManufacturerResource::class;

    use StripsSlugFormState;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->stripSlugFormState($data);
    }
}
