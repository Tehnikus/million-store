<?php

namespace App\Filament\Clusters\MetaEditor\Livewire;

use App\Models\Catalog\FacetPage;
use Filament\Facades\Filament;
// use Filament\Support\Colors\Color;
// use Filament\Support\Enums\FontWeight;
// use Filament\Support\Enums\TextSize;
// use Filament\Tables\Columns\Layout\Grid;
// use Filament\Tables\Columns\Layout\Stack;
// use Filament\Tables\Columns\TextColumn;
// use Filament\Tables\Columns\TextInputColumn;
use Illuminate\Database\Eloquent\Model;

class FacetPagesResultsTable extends AbstractResultsTable
{
    protected function entityType(): string
    {
        return 'facet-page';
    }

    protected function translatableFields(): array
    {
        return ['name', 'meta_title', 'h1', 'meta_description'];
    }

    protected function resolveEntity(int|string $id): ?Model
    {
        $storeId = once(fn() => Filament::getTenant()->id);
        return FacetPage::query()
            ->where('store_id', $storeId)
            ->whereKey($id)
            ->first();
    }
}