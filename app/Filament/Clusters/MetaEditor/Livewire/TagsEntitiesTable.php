<?php

namespace App\Filament\Clusters\MetaEditor\Livewire;

use App\Filament\Resources\Tags\TagResource;
use App\Models\Catalog\Tag;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TagsEntitiesTable extends AbstractEntitiesTable
{
    private function storeData(): Model {
        return once(fn() => Filament::getTenant());
    }

    protected function getEntitiesQuery(): Builder
    {
        return Tag::query()->where('store_id', $this->storeData()->id);
    }

    protected function editRecordUrl(Model $record): ?string
    {
        return TagResource::getUrl('edit', ['record' => $record]);
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
            'parent'            => null, // TODO Add parent facet page here
            'product_count'     => null, // TODO
        ];
    }
}