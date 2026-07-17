<?php

namespace App\Filament\Resources\Slugs\Pages;

use App\Filament\Resources\Slugs\SlugResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSlugs extends ListRecords
{
    protected static string $resource = SlugResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
