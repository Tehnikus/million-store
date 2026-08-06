<?php

namespace App\Filament\Resources\Options\Tables;

use App\Filament\Support\Columns\ConversionImageColumn;
use App\Filament\Support\Columns\MultilangTextColumn;
use App\Models\Catalog\OptionValue;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class OptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('values')) // Eager loading of option values
            ->columns([
                ConversionImageColumn::make('images')
                    ->conversion('miniature')
                    ->label(__('admin.common.fields.image')),

                MultilangTextColumn::make('name')
                    ->recordColumnAll(fn ($record) => $record->getTranslations('name') ?? [])
                    ->wrapHeader()
                    ->label(__('admin.catalog.options.fields.group')),

                TextColumn::make('values')
                    ->label(__('admin.catalog.options.fields.values'))
                    ->badge()
                    ->limitList(10)
                    ->expandableLimitedList()
                    ->formatStateUsing(fn (OptionValue $state) => $state->name)
                    ->color(function (OptionValue $state) {
                        if (! $state->is_active) {
                            return 'gray';
                        }

                        if (! $state->show_in_facets) {
                            return 'warning';
                        }

                        return 'success';
                    }),

                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(function ($record) {
                        if ($record->type == 'radio') return __('admin.catalog.options.fields.radio');
                        if ($record->type == 'checkbox') return __('admin.catalog.options.fields.checkbox');
                    })
                    ->alignment(Alignment::Center)
                    ->label(__('admin.catalog.options.fields.type')),

                ToggleColumn::make('is_active')
                    ->sortable()
                    ->width('100px')
                    ->alignment(Alignment::Center)
                    ->label(__('admin.catalog.options.fields.is_active')),

                ToggleColumn::make('show_in_facets')
                    ->sortable()
                    ->width('100px')
                    ->wrapHeader()
                    ->alignment(Alignment::Center)
                    ->label(__('admin.catalog.options.fields.show_in_facets')),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
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
