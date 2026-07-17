<?php

namespace App\Filament\Resources\Stores\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Display all currencies table on store creation page
 */
class CurrenciesRelationManager extends RelationManager
{
    protected static string $relationship = 'currencies';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('iso_code')
            ->inverseRelationship('stores')
            
            ->modifyQueryUsing(function (Builder $query) {
                $storeId = $this->getOwnerRecord()->id;

                // Reset all Eloquent auto filters 
                $baseQuery = $query->getQuery();
                $baseQuery->joins = [];
                $baseQuery->wheres = [];
                $baseQuery->bindings['join'] = [];
                $baseQuery->bindings['where'] = [];

                // Select all countries and join to pivot-flags with readable aliases
                return $query->from('currencies')
                    ->leftJoin('store_currencies', function ($join) use ($storeId) {
                        $join->on('currencies.id', '=', 'store_currencies.currency_id')
                             ->where('store_currencies.store_id', '=', $storeId);
                    })
                    ->select([
                        'currencies.id as id', 
                        'currencies.name as name', 
                        'currencies.iso_code as iso_code',
                        // IMPORTANT! Rename flags, so Filament does not confuse them with similar named fieldsfrom Country model
                        'store_currencies.is_active as active_status',
                        
                    ]);
            })
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.currencies.fields.name')),

                TextColumn::make('iso_code')
                    ->label(__('admin.currencies.fields.iso_code')),

                // is_active switch
                ToggleColumn::make('active_status')
                    ->label(__('admin.currencies.fields.is_active'))
                    ->state(fn (Model $record) => (bool) $record->active_status)
                    // Block switch from change if country is default. Default country cannot be disabled
                    ->disabled(fn (Model $record) => (bool) $record->default_status)
                    ->updateStateUsing(function (Model $record, $state) {
                        $storeId = $this->getOwnerRecord()->id;

                       // Additional check in backend: if country is default, it cannot be disabled
                        if ($record->default_status && !$state) {
                            return;
                        }

                        // Update DB record
                        DB::table('store_currencies')->updateOrInsert(
                            ['store_id' => $storeId, 'currency_id' => $record->id],
                            ['is_active' => (bool) $state]
                        );

                        // Update `updated_at` column in parent `stores` table, so cache is consistent to settings
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
        return __('admin.currencies.navigation_label');
    }
}
