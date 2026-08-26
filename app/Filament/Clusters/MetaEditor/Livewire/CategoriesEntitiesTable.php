<?php

namespace App\Filament\Clusters\MetaEditor\Livewire;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Catalog\Category;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CategoriesEntitiesTable extends AbstractEntitiesTable
{
    private function storeData(): Model {
        return once(fn() => Filament::getTenant());
    }

    protected function getEntitiesQuery(): Builder
    {
        return Category::query()->where('store_id', $this->storeData()->id);
    }

    protected function editRecordUrl(Model $record): ?string
    {
        return CategoryResource::getUrl('edit', ['record' => $record]);
    }

    protected function entityColumns(): array
    {
        return [
            TextColumn::make('name')->searchable(isIndividual: true)->label(__('admin.common.fields.name')),
            TextColumn::make('meta_title')->searchable(isIndividual: true)->label(__('admin.common.fields.meta_title')),
            TextColumn::make('h1')->searchable(isIndividual: true)->label(__('admin.common.fields.h1')),
            TextColumn::make('meta_description')->searchable(isIndividual: true)->label(__('admin.common.fields.meta_description'))->limit(60),
        ];
    }

    /**
     * Pass variables to staging table
     * @param Model $record
     * @return array{h1: mixed, id: mixed, meta_description: mixed, meta_title: mixed, name: mixed}
     */
    protected function toResultRow(Model $record): array
    {
        return [
            'id'                => $record->id,
            'name'              => $record->getTranslations('name'),
            'meta_title'        => $record->getTranslations('meta_title'),
            'h1'                => $record->getTranslations('h1'),
            'meta_description'  => $record->getTranslations('meta_description'),
            'parent'            => Category::find($record->parent_id)?->getTranslations('name'),
            'product_count'     => null, // TODO
        ];
    }
}