<?php

namespace App\Filament\Resources\FacetPages\Pages;

use App\Filament\Resources\FacetPages\FacetPageResource;
use App\Filament\Resources\FacetPages\Schemas\FacetPageForm;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFacetPage extends EditRecord
{
    protected static string $resource = FacetPageResource::class;
    protected ?array $pendingRootFacet = null;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $root = $this->record->facetIndex()->where('is_root', true)->first();

        if ($root) {
            $data['root_facet_type_id']  = $root->facet_type_id->value; // explicitly scalar, as options() expects
            $data['root_facet_value_id'] = $root->facet_value_id;
            $data['root_facet_group_id'] = $root->facet_group_id;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        [$data, $this->pendingRootFacet] = FacetPageForm::extractRootFacet($data);
        return $data;
    }

    protected function afterSave(): void
    {
        FacetPageForm::saveRootFacet($this->record, $this->pendingRootFacet);
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
