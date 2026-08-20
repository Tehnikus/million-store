<?php

namespace App\Filament\Clusters\MetaEditor\Livewire;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Catalog\Category;
use App\Models\Catalog\Manufacturer;
use App\Models\Catalog\ProductDescription;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProductsEntitiesTable extends AbstractEntitiesTable
{
    protected function getEntitiesQuery(): Builder
    {
        return ProductDescription::query()->where('store_id', Filament::getTenant()->id);
    }

    protected function editRecordUrl(Model $record): ?string
    {
        return ProductResource::getUrl('edit', ['record' => $record]);
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

    protected function toResultRow(Model $record): array
    {
        return [
            'id'                => $record->id,
            'name'              => $record->getTranslations('name'),
            'meta_title'        => $record->getTranslations('meta_title'),
            'h1'                => $record->getTranslations('h1'),
            'meta_description'  => $record->getTranslations('meta_description'),
            'parent'            => Category::find($record->parent_id)?->getTranslations('name'),
            'manufacturer'      => Manufacturer::find($record->manufacturer_id)->getTranslations('name')
        ];
    }
}