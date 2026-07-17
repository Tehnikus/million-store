<?php

namespace App\Filament\Resources\Currencies\Pages;

use App\Filament\Resources\Currencies\CurrencyResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateCurrency extends CreateRecord
{
    use RedirectsToIndex;
    protected static string $resource = CurrencyResource::class;
}
