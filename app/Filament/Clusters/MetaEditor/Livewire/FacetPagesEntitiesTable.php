<?php

namespace App\Filament\Clusters\MetaEditor\Livewire;

use App\Filament\Resources\FacetPages\FacetPageResource;
use App\Models\Catalog\FacetPage;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FacetPagesEntitiesTable extends AbstractEntitiesTable
{
    protected function getEntitiesQuery(): Builder
    {
        $storeId = once(fn() => Filament::getTenant()->id);
        return FacetPage::query()->where('store_id', $storeId);
    }

    protected function editRecordUrl(Model $record): ?string
    {
        return FacetPageResource::getUrl('edit', ['record' => $record]);
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
        ];
    }
}