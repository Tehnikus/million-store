<?php

namespace App\Filament\Resources\Stores;

use App\Filament\Resources\Stores\Pages\CreateStore;
use App\Filament\Resources\Stores\Pages\EditStore;
use App\Filament\Resources\Stores\Pages\ListStores;
use App\Filament\Resources\Stores\Schemas\StoreForm;
use App\Filament\Resources\Stores\Tables\StoresTable;
use App\Models\Store;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Stores\RelationManagers\LanguagesRelationManager;
use App\Filament\Support\NavigationGroup;


class StoreResource extends Resource
{
    protected static bool $isScopedToTenant = false;
    protected static ?string $model = Store::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;
    protected static ?int $navigationSort = 6;
    protected static string | \UnitEnum | null $navigationGroup = NavigationGroup::GlobalSettings;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return StoreForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StoresTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStores::route('/'),
            'create' => CreateStore::route('/create'),
            'edit' => EditStore::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.stores.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.stores.model_label_singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.stores.navigation_label');
    }

    // Relation managers
    public static function getRelations(): array
    {
        return [
            RelationManagers\LanguagesRelationManager::class,
            RelationManagers\CurrenciesRelationManager::class,
            RelationManagers\CountriesRelationManager::class,
        ];
    }

    /**
     * Greedy data loading
     * Get data related to stores from tables
     * @return Builder
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['languages', 'currencies', 'countries']); // Greedy related tables store data loading
    }

    // Skip global search
    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }
}
