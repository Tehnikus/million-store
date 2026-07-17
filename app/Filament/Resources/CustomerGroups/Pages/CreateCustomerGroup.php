<?php

namespace App\Filament\Resources\CustomerGroups\Pages;

use App\Filament\Resources\CustomerGroups\CustomerGroupResource;
use App\Filament\Concerns\RedirectsToIndex;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerGroup extends CreateRecord
{
    use RedirectsToIndex;
    protected static string $resource = CustomerGroupResource::class;
}
