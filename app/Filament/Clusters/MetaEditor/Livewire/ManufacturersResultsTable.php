<?php

namespace App\Filament\Clusters\MetaEditor\Livewire;

use App\Models\Catalog\Manufacturer;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

class ManufacturersResultsTable extends AbstractResultsTable
{
    protected function entityType(): string
    {
        return 'manufacturers';
    }

    protected function translatableFields(): array
    {
        return ['name', 'meta_title', 'h1', 'meta_description'];
    }

    protected function resolveEntity(int|string $id): ?Model
    {
        $storeId = once(fn() => Filament::getTenant()->id);
        return Manufacturer::query()
            ->where('store_id', $storeId)
            ->whereKey($id)
            ->first();
    }
}