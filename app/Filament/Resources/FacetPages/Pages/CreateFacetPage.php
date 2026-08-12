<?php

namespace App\Filament\Resources\FacetPages\Pages;

use App\Filament\Resources\FacetPages\FacetPageResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\StripsSlugFormState;

class CreateFacetPage extends CreateRecord
{
    use StripsSlugFormState;
    protected static string $resource = FacetPageResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->stripSlugFormState($data);
    }
}
