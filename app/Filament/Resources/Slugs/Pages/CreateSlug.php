<?php

namespace App\Filament\Resources\Slugs\Pages;

use App\Filament\Resources\Slugs\SlugResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateSlug extends CreateRecord
{
    use RedirectsToIndex;
    protected static string $resource = SlugResource::class;
}
