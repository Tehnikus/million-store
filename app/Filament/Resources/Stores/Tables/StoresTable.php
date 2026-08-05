<?php

namespace App\Filament\Resources\Stores\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Global\Store;
class StoresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Eager loading of relations
                $query->with(['countries', 'languages', 'currencies']);
            })
            ->columns([
                TextColumn::make('name')->label(__('admin.stores.fields.name')),
                TextColumn::make('host')->label(__('admin.stores.fields.host')),
                TextColumn::make('countries.name')->label(__('admin.stores.fields.countries'))->badge()->width('1%')->alignment(Alignment::Center),
                TextColumn::make('languages.locale')->label(__('admin.stores.fields.languages'))->badge()->width('1%')->alignment(Alignment::Center)
                    // Get store active languages
                    ->getStateUsing(function (Store $record) {
                        return $record->languages
                            ->where('pivot.is_active', true)
                            ->pluck('name')
                            ->toArray();
                    })
                    // Highlight is_default language with 'success' color
                    ->color(function (string $state, Store $record) {
                        $isDefault = $record->languages
                            ->where('name', $state)
                            ->first()
                            ?->pivot->is_default;
                        return $isDefault ? 'success' : 'primary'; 
                    }),
                TextColumn::make('currencies.sign')->label(__('admin.stores.fields.currencies'))->badge()->width('1%')->alignment(Alignment::Center),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
