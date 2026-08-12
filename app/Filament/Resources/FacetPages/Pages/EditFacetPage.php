<?php

namespace App\Filament\Resources\FacetPages\Pages;

use App\Filament\Resources\FacetPages\FacetPageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Concerns\StripsSlugFormState;

class EditFacetPage extends EditRecord
{
    protected static string $resource = FacetPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    use StripsSlugFormState;
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->stripSlugFormState($data);
    }
}
