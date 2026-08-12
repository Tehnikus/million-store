<?php

namespace App\Filament\Resources\FacetPages\Pages;

use App\Filament\Resources\FacetPages\FacetPageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFacetPages extends ListRecords
{
    protected static string $resource = FacetPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
