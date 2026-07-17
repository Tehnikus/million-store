<?php

namespace App\Filament\Resources\Stores\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CountriesRelationManager extends RelationManager
{
    protected static string $relationship = 'countries';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

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
                return $query->from('countries')
                    ->leftJoin('store_countries', function ($join) use ($storeId) {
                        $join->on('countries.id', '=', 'store_countries.country_id')
                             ->where('store_countries.store_id', '=', $storeId);
                    })
                    ->select([
                        'countries.id as id', 
                        'countries.name as name', 
                        'countries.iso_code as iso_code',
                        // IMPORTANT! Rename flags, so Filament does not confuse them with similar named fieldsfrom Country model
                        'store_countries.is_active as active_status',
                        
                    ]);
            })
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.countries.fields.name')),

                TextColumn::make('iso_code')
                    ->label(__('admin.countries.fields.iso_code')),

                // is_active switch
                ToggleColumn::make('active_status')
                    ->label(__('admin.countries.fields.is_active'))
                    ->state(fn (Model $record) => (bool) $record->active_status)
                    // Block switch from change if country is default. Default country cannot be disabled
                    ->disabled(fn (Model $record) => (bool) $record->default_status)
                    ->updateStateUsing(function (Model $record, $state) {
                        $storeId = $this->getOwnerRecord()->id;

                        // Additional check in backend: if country is default, it cannot be disabled
                        if ($record->default_status && !$state) {
                            return;
                        }

                        DB::table('store_countries')->updateOrInsert(
                            ['store_id' => $storeId, 'country_id' => $record->id],
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
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.countries.navigation_label');
    }
}
