<?php

namespace App\Filament\Resources\StoreInfoPages;

use App\Filament\Resources\StoreInfoPages\Pages\CreateStoreInfoPage;
use App\Filament\Resources\StoreInfoPages\Pages\EditStoreInfoPage;
use App\Filament\Resources\StoreInfoPages\Pages\ListStoreInfoPages;
use App\Filament\Resources\StoreInfoPages\Schemas\StoreInfoPageForm;
use App\Filament\Resources\StoreInfoPages\Tables\StoreInfoPagesTable;
use App\Models\Store\StoreInfoPage;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

use App\Filament\Support\AdminMenu\NavigationItem;
use App\Filament\Support\AdminMenu\HasCentralizedNavigation;

class StoreInfoPageResource extends Resource
{
    protected static ?string $model = StoreInfoPage::class;
    protected static ?string $recordTitleAttribute = 'name';
    protected static bool $isGloballySearchable = false;

    public static function form(Schema $schema): Schema
    {
        return StoreInfoPageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StoreInfoPagesTable::configure($table);
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
            'index' => ListStoreInfoPages::route('/'),
            'create' => CreateStoreInfoPage::route('/create'),
            'edit' => EditStoreInfoPage::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count =  static::getModel()::where('is_active', false)->count();
        return $count > 0 ? (string) $count : null;
    }

    // Skip on global search, no need to show info pages
    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }

    // Some repeating navigation methods in one place
    use HasCentralizedNavigation;
    protected static function getMenuConfig(): NavigationItem
    {
        return NavigationItem::InfoPages;
    }
}
