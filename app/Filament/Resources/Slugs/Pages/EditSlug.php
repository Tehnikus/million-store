<?php

namespace App\Filament\Resources\Slugs\Pages;

use App\Filament\Resources\Slugs\SlugResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSlug extends EditRecord
{
    protected static string $resource = SlugResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
