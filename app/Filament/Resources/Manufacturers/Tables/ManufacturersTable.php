<?php

namespace App\Filament\Resources\Manufacturers\Tables;

use App\Domain\Catalog\Actions\DeletionWarnings;
use App\Domain\Catalog\FacetType;
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Filament\Support\Columns\{ConversionImageColumn, MultilangTextColumn};
use App\Models\Catalog\Manufacturer;
use Filament\Actions\{DeleteAction, DeleteBulkAction, EditAction};
use Filament\Facades\Filament;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\{SelectColumn, TextColumn, ToggleColumn};
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class ManufacturersTable
{
    public static function configure(Table $table): Table
    {
        $store = Filament::getTenant();
        return $table
            ->emptyStateIcon(NavigationItem::Manufacturers->icon())
            ->emptyStateHeading(__('admin.catalog.manufacturers.navigation_label'))
            ->emptyStateDescription(__('admin.catalog.manufacturers.table.empty_state'))
            ->modifyQueryUsing(fn($query) => $query->with('parent')->withCount('products')->where('store_id', $store->id))
            ->columns([
                ConversionImageColumn::make('images')
                    ->conversion('miniature')
                    ->label(__('admin.common.fields.image')),
                
                MultilangTextColumn::make('name')
                    ->recordColumnAll(fn ($record) => $record->getTranslations('name') ?? [])
                    ->wrapHeader()
                    ->label(__('admin.catalog.manufacturers.model_label_singular')),

                SelectColumn::make('parent_id')
                    ->options(
                        fn(?Manufacturer $record) => $record
                        ? Manufacturer::manufacturerChoices($store->id)->except([...$record->descendantIds(), $record->id])
                        : Manufacturer::manufacturerChoices($store->id)
                    )
                    ->searchableOptions(true)
                    ->native(false)
                    ->wrapHeader()
                    ->width('220px')
                    ->label(__('admin.catalog.manufacturers.tabs.content.labels.parent_id'))
                    ->placeholder(__('admin.catalog.manufacturers.tabs.content.labels.is_root')),

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
                    ->label(__('admin.catalog.manufacturers.tabs.content.labels.is_active'))
                    ->afterStateUpdated(function ($livewire) {
                        $livewire->dispatch('refresh-sidebar');
                    }),

                ToggleColumn::make('show_in_facets')
                    ->sortable()
                    ->width('100px')
                    ->wrapHeader()
                    ->alignment(Alignment::Center)
                    ->label(__('admin.catalog.manufacturers.tabs.content.labels.show_in_facets')),
                
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->groups([
                Group::make('parent_id')
                    ->label(__('admin.catalog.manufacturers.tabs.content.labels.parent_id'))
                    ->getTitleFromRecordUsing(function (Manufacturer $record) {
                        return $record->parent?->name ?? __('admin.catalog.manufacturers.tabs.content.labels.is_root');
                    })
                    ->orderQueryUsing(
                        fn(Builder $query, string $direction) => $query
                        ->orderByRaw('parent_id IS NULL DESC') //  parent (true) before reply (false)
                        ->orderBy('sort_order', $direction)
                    )
            ])
            ->defaultGroup('parent_id')
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription(fn(Manufacturer $record): HtmlString =>
                        DeletionWarnings::affectedWarningMessage(FacetType::Manufacturer, [$record->id], $store->id)),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->modalDescription(fn($records): HtmlString =>
                        DeletionWarnings::affectedWarningMessage(FacetType::Manufacturer, $records->modelKeys(), $store->id)),
            ]);
    }
}
