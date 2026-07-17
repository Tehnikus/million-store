<?php

namespace App\Filament\Resources\Stores\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LanguagesRelationManager extends RelationManager
{
    protected static string $relationship = 'languages';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->inverseRelationship('stores')
            
            ->modifyQueryUsing(function (Builder $query) {
                $storeId = $this->getOwnerRecord()->id;

                // Reset default Filament getQuery filters
                $baseQuery = $query->getQuery();
                $baseQuery->joins = [];
                $baseQuery->wheres = [];
                $baseQuery->bindings['join'] = [];
                $baseQuery->bindings['where'] = [];

                // Select all languages and join to pivot-flags with readable aliases
                return $query->from('languages')
                    ->leftJoin('store_languages', function ($join) use ($storeId) {
                        $join->on('languages.id', '=', 'store_languages.language_id')
                             ->where('store_languages.store_id', '=', $storeId);
                    })
                    ->select([
                        'languages.id as id', 
                        'languages.name as name', 
                        'languages.iso_code as iso_code',
                        // IMPORTANT! Rename flags, so Filament does not confuse them with similar named fieldsfrom Language model
                        'store_languages.is_active as active_status',
                        'store_languages.is_default as default_status',
                    ]);
            })
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.languages.fields.name')),

                TextColumn::make('iso_code')
                    ->label(__('admin.languages.fields.iso_code')),

                // is_active switch
                ToggleColumn::make('active_status')
                    ->label(__('admin.languages.fields.is_active'))
                    ->state(fn (Model $record) => (bool) $record->active_status)
                    // Block switch from change if language is default. Default language cannot be disabled
                    ->disabled(fn (Model $record) => (bool) $record->default_status)
                    ->updateStateUsing(function (Model $record, $state) {
                        $storeId = $this->getOwnerRecord()->id;

                        // Additional check in backend: if language is default, it cannot be disabled
                        if ($record->default_status && !$state) {
                            return;
                        }

                        DB::table('store_languages')->updateOrInsert(
                            ['store_id' => $storeId, 'language_id' => $record->id],
                            ['is_active' => (bool) $state]
                        );
                    })
                    ->width('1%'),

                // is_default switch
                ToggleColumn::make('default_status')
                    ->label(__('admin.languages.fields.is_default'))
                    ->state(fn (Model $record) => (bool) $record->default_status)
                    ->updateStateUsing(function (Model $record, $state) {
                        $storeId = $this->getOwnerRecord()->id;

                        if (!$state) {
                            return;
                        }

                        // 1. Reset all languages belonging to this store to false
                        DB::table('store_languages')
                            ->where('store_id', $storeId)
                            ->update(['is_default' => false]);

                        // 2. Set cuurent language to is_default => true and is_active => true. Default language is always active 
                        DB::table('store_languages')->updateOrInsert(
                            ['store_id' => $storeId, 'language_id' => $record->id],
                            ['is_default' => true, 'is_active' => true]
                        );

                        // Update updated_at column in parent `stores` table, so cache is consistent to settings
                        $this->getOwnerRecord()->touch();
                    })
                    ->width('1%'),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }

    // Translate tab title
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.languages.navigation_label');
    }
}
