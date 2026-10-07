<?php

namespace App\Filament\Resources\Tags\Tables;

use App\Filament\Support\AdminMenu\NavigationItem;
use App\Filament\Support\Columns\ConversionImageColumn;
use App\Filament\Support\Columns\MultilangTextColumn;
use App\Models\Catalog\Tag;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class TagsTable
{
    public static function configure(Table $table): Table
    {
        $store = Filament::getTenant();
        return $table
            ->emptyStateIcon(NavigationItem::Tags->icon())
            ->emptyStateHeading(__('admin.catalog.tags.navigation_label'))
            ->emptyStateDescription(__('admin.catalog.tags.table.empty_state'))
            ->modifyQueryUsing(fn($query) => $query->with('products')->where('store_id', $store->id))
            ->columns([
                ConversionImageColumn::make('images')
                    ->conversion('miniature'),

                MultilangTextColumn::make('name')
                    ->recordColumnAll(fn ($record) => $record->getTranslations('name') ?? [])
                    ->wrapHeader()
                    ->label(__('admin.catalog.tags.model_label_singular')),

                TextColumn::make('products')
                    ->getStateUsing(fn(Tag $record) => $record->products->count())
                    ->color(fn($record) => $record->products->count() > 0 ? 'success' : 'danger')
                    ->sortable()
                    ->badge()
                    ->width('1%')
                    ->alignment(Alignment::Center)
                    ->label(__('admin.catalog.products.navigation_label')),

                ToggleColumn::make('is_active')
                    ->sortable()
                    ->width('100px')
                    ->alignment(Alignment::Center)
                    ->label(__('admin.catalog.tags.tabs.content.labels.is_active'))
                    ->afterStateUpdated(function ($livewire) {
                        $livewire->dispatch('refresh-sidebar');
                    }),

                ToggleColumn::make('show_in_facets')
                    ->sortable()
                    ->width('100px')
                    ->wrapHeader()
                    ->alignment(Alignment::Center)
                    ->label(__('admin.catalog.tags.tabs.content.labels.show_in_facets')),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
