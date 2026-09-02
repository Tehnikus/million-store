<?php

namespace App\Filament\Resources\Manufacturers\Tables;

use App\Filament\Support\Columns\ConversionImageColumn;
use App\Filament\Support\Columns\MultilangTextColumn;
use App\Models\Catalog\Manufacturer;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ManufacturersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ConversionImageColumn::make('images')
                    ->conversion('miniature')
                    ->label(__('admin.common.fields.image')),
                
                MultilangTextColumn::make('name')
                    ->recordColumnAll(fn ($record) => $record->getTranslations('name') ?? [])
                    ->wrapHeader()
                    ->label(__('admin.catalog.manufacturers.model_label_singular')),

                SelectColumn::make('parent_id')
                    ->optionsRelationship(name: 'parent', titleAttribute: 'name')
                    ->wrapHeader()
                    ->width('220px')
                    ->label(__('admin.catalog.manufacturers.fields.parent_id')),

                ToggleColumn::make('is_active')
                    ->sortable()
                    ->width('100px')
                    ->alignment(Alignment::Center)
                    ->label(__('admin.catalog.manufacturers.fields.is_active')),

                ToggleColumn::make('show_in_facets')
                    ->sortable()
                    ->width('100px')
                    ->wrapHeader()
                    ->alignment(Alignment::Center)
                    ->label(__('admin.catalog.manufacturers.fields.show_in_facets')),
                
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->groups([
                Group::make('parent_id')
                    ->label(__('admin.catalog.manufacturers.fields.parent_id'))
                    ->getTitleFromRecordUsing(function (Manufacturer $record) {
                        return $record->parent?->name ?? __('admin.catalog.manufacturers.fields.is_root');
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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
