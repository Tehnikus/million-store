<?php

namespace App\Filament\Resources\Slugs\Pages;

use App\Filament\Resources\Slugs\SlugResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Concerns\RedirectsToIndex;

class EditSlug extends EditRecord
{
    use RedirectsToIndex;
    protected static string $resource = SlugResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
