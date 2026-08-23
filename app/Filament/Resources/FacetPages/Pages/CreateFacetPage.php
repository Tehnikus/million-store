<?php

namespace App\Filament\Resources\FacetPages\Pages;

use App\Filament\Resources\FacetPages\FacetPageResource;
use App\Filament\Resources\FacetPages\Schemas\FacetPageForm;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\StripsSlugFormState;

// CreateFacetPage.php
class CreateFacetPage extends CreateRecord
{
    use StripsSlugFormState;
    protected static string $resource = FacetPageResource::class;
    protected ?array $pendingRootFacet = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        [$data, $this->pendingRootFacet] = FacetPageForm::extractRootFacet($this->stripSlugFormState($data));
        return $data;
    }

    protected function afterCreate(): void
    {
        FacetPageForm::saveRootFacet($this->record, $this->pendingRootFacet);
    }
}
