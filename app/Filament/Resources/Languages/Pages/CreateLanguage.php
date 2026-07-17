<?php

namespace App\Filament\Resources\Languages\Pages;

use App\Filament\Resources\Languages\LanguageResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateLanguage extends CreateRecord
{
    use RedirectsToIndex;
    protected static string $resource = LanguageResource::class;
}
