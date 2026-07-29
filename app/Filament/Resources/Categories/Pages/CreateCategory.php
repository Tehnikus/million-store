<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\StripsSlugFormState;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;

    use StripsSlugFormState;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->stripSlugFormState($data);
    }
}
