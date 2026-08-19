<?php

namespace App\Filament\Clusters\MetaEditor\Livewire;

use App\Models\Catalog\ProductDescription;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

class ProductsResultsTable extends AbstractResultsTable
{
    protected function entityType(): string
    {
        return 'product';
    }

    protected function translatableFields(): array
    {
        return ['name', 'meta_title', 'h1', 'meta_description'];
    }

    protected function resolveEntity(int|string $id): ?Model
    {
        $storeId = once(fn() => Filament::getTenant()->id);
        return ProductDescription::query()
            ->where('store_id', $storeId)
            ->whereKey($id)
            ->first();
    }
}