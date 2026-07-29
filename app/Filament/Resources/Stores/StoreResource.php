<?php

namespace App\Filament\Resources\Stores;

use App\Filament\Resources\Stores\Pages\CreateStore;
use App\Filament\Resources\Stores\Pages\EditStore;
use App\Filament\Resources\Stores\Pages\ListStores;
use App\Filament\Resources\Stores\Schemas\StoreForm;
use App\Filament\Resources\Stores\Tables\StoresTable;
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Filament\Support\AdminMenu\HasCentralizedNavigation;
use App\Models\Global\Store;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StoreResource extends Resource
{
    protected static ?string $model = Store::class;
    protected static bool $isScopedToTenant = false;
    protected static ?string $recordTitleAttribute = 'name';
    protected static bool $isGloballySearchable = false;

    public static function form(Schema $schema): Schema
    {
        return StoreForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StoresTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStores::route('/'),
            'create' => CreateStore::route('/create'),
            'edit' => EditStore::route('/{record}/edit'),
        ];
    }

    // Greedy loading
    public static function getEloquentQuery(): Builder {
        return parent::getEloquentQuery()
            ->with(['countries', 'languages', 'currencies']);
    }

    // Skip global search DB columns
    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }

    // Some repeating navigation methods in one place
    use HasCentralizedNavigation;
    protected static function getMenuConfig(): NavigationItem
    {
        return NavigationItem::Stores;
    }
}
