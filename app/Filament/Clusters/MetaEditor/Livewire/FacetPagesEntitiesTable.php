<?php

namespace App\Filament\Clusters\MetaEditor\Livewire;

use App\Domain\Catalog\FacetType;
use App\Filament\Resources\FacetPages\FacetPageResource;
use App\Models\Catalog\Category;
use App\Models\Catalog\FacetPage;
use App\Models\Catalog\Manufacturer;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FacetPagesEntitiesTable extends AbstractEntitiesTable
{
    protected function getEntitiesQuery(): Builder
    {
        $storeId = once(fn() => Filament::getTenant()->id);
        return FacetPage::query()->where('store_id', $storeId)->with('facetIndex');
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
            'parent'            => $this->resolveParent($record),
            'manufacturer'      => $this->resolveManufacturer($record),
        ];
    }

    private function resolveParent(Model $facetPage): ?array
    {
        $parentFacet = $facetPage->facetIndex
            ->firstWhere('is_root', true);

        if (!$parentFacet) return null;

        if ($parentFacet->facet_type_id == FacetType::Category->value) {
            return Category::find($parentFacet->facet_value_id)?->getTranslations('name');
        }
        if ($parentFacet->facet_type_id == FacetType::Manufacturer->value) {
            return Manufacturer::find($parentFacet->facet_value_id)?->getTranslations('name');
        }
    }

    private function resolveManufacturer(Model $facetPage): ?array
    {
        $manufacturerFacet = $facetPage->facetIndex
            ->firstWhere('facet_type_id', FacetType::Manufacturer->value);

        if (! $manufacturerFacet) {
            return null;
        }

        return Manufacturer::find($manufacturerFacet->facet_value_id)?->getTranslations('name');
    }
}