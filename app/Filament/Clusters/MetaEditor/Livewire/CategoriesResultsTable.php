<?php

namespace App\Filament\Clusters\MetaEditor\Livewire;

use App\Models\Catalog\Category;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

class CategoriesResultsTable extends AbstractResultsTable
{
    protected function entityType(): string
    {
        return 'category';
    }

    /**
     * List of translatable fields so DB knows how to write changes in AbstractResultsTable::saveResults()
     * @return string[]
     */
    protected function translatableFields(): array
    {
        return ['name', 'meta_title', 'h1', 'meta_description'];
    }

    /**
     * Check if category exists before saving in AbstractResultsTable::saveResults()
     * @param int|string $id
     * @return Category|\stdClass|null
     */
    protected function resolveEntity(int|string $id): ?Model
    {
        $storeId = once(fn() => Filament::getTenant()->id);
        return Category::query()
            ->where('store_id', $storeId)
            ->whereKey($id)
            ->first();
    }
}