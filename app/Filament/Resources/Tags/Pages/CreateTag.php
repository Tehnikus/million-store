<?php

namespace App\Filament\Resources\Tags\Pages;

use App\Filament\Concerns\StripsSlugFormState;
use App\Filament\Resources\Tags\TagResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTag extends CreateRecord
{
    protected static string $resource = TagResource::class;
    
    use StripsSlugFormState;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->stripSlugFormState($data);
    }
}
