<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Domain\Catalog\Actions\PurgeDeletedCatalogValue;
use App\Domain\Catalog\FacetType;
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Filament\Support\Columns\ConversionImageColumn;
use App\Filament\Support\Columns\MultilangTextColumn;
use App\Models\Catalog\Category;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        $store = Filament::getTenant();
        return $table
            ->emptyStateIcon(NavigationItem::Categories->icon())
            ->emptyStateHeading(__('admin.catalog.categories.navigation_label'))
            ->emptyStateDescription(__('admin.catalog.categories.table.empty_state'))
            ->modifyQueryUsing(fn($query) => $query->with('parent')->withCount('products')->where('store_id', $store->id))
            ->columns([
                ConversionImageColumn::make('images')
                    ->conversion('miniature')
                    ->label(__('admin.common.fields.image')),

                MultilangTextColumn::make('name')
                    ->recordColumnAll(fn ($record) => $record->getTranslations('name') ?? [])
                    ->wrapHeader()
                    ->label(__('admin.catalog.categories.model_label_singular')),

                SelectColumn::make('parent_id')
                    ->options(
                        fn(?Category $record) => $record
                        ? Category::categoryChoices($store->id)->except([...$record->descendantIds(), $record->id])
                        : Category::categoryChoices($store->id)
                    )
                    ->searchableOptions(true)
                    ->native(false)
                    ->width('220px')
                    ->wrapHeader()
                    ->placeholder(__('admin.catalog.categories.tabs.content.labels.is_root'))
                    ->label(__('admin.catalog.categories.tabs.content.labels.parent_id')),

                TextColumn::make('products_count')
                    ->color(fn(int $state) => $state > 0 ? 'success' : 'danger')
                    ->sortable()
                    ->badge()
                    ->width('1%')
                    ->alignment(Alignment::Center)
                    ->label(__('admin.catalog.products.navigation_label')),

                ToggleColumn::make('is_active')
                    ->sortable()
                    ->width('100px')
                    ->alignment(Alignment::Center)
                    ->label(__('admin.catalog.categories.tabs.content.labels.is_active'))
                    ->afterStateUpdated(function ($livewire) {
                        $livewire->dispatch('refresh-sidebar');
                        // TODO: Rebuild sitemap here
                    }),

                ToggleColumn::make('show_in_facets')
                    ->sortable()
                    ->width('180px')
                    ->wrapHeader()
                    ->alignment(Alignment::Center)
                    ->label(__('admin.catalog.categories.tabs.content.labels.show_in_facets')),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                //
            ])
            ->groups([
                Group::make('parent_id')
                    ->label(__('admin.catalog.categories.tabs.content.labels.parent_id'))
                    ->getTitleFromRecordUsing(function (Category $record) {
                        return $record->parent?->name ?? __('admin.catalog.categories.tabs.content.labels.is_root');
                    })
                    ->orderQueryUsing(
                        fn(Builder $query, string $direction) => $query
                        ->orderByRaw('parent_id IS NULL DESC') //  parent (true) before reply (false)
                        ->orderBy('sort_order', $direction)
                    )
            ])
            ->defaultGroup('parent_id')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription(fn(Category $record): HtmlString =>
                        PurgeDeletedCatalogValue::affectedWarningMessage(FacetType::Category, [$record->id], $store->id))
                    ->after(fn($livewire) => $livewire->dispatch('refresh-sidebar')),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->modalDescription(fn($records): HtmlString =>
                        PurgeDeletedCatalogValue::affectedWarningMessage(FacetType::Category, $records->modelKeys(), $store->id))
                    ->after(fn($livewire) => $livewire->dispatch('refresh-sidebar')),
            ]);
    }
}
