<?php

namespace App\Filament\Resources\StoreInfoPages;

use App\Filament\Resources\StoreInfoPages\Pages\CreateStoreInfoPage;
use App\Filament\Resources\StoreInfoPages\Pages\EditStoreInfoPage;
use App\Filament\Resources\StoreInfoPages\Pages\ListStoreInfoPages;
use App\Filament\Resources\StoreInfoPages\Schemas\StoreInfoPageForm;
use App\Filament\Resources\StoreInfoPages\Tables\StoreInfoPagesTable;
use App\Models\StoreInfoPage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Filament\Support\NavigationGroup;


class StoreInfoPageResource extends Resource
{
    protected static ?string $model = StoreInfoPage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInformationCircle;

    protected static ?int $navigationSort = 2;

    protected static string | \UnitEnum | null $navigationGroup = NavigationGroup::StoreSettings;

    protected static ?string $recordTitleAttribute = 'name';

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

    public static function getNavigationLabel(): string
    {
        return __('admin.info_pages.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.info_pages.model_label_singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.info_pages.navigation_label');
    }

    public static function getNavigationBadge(): ?string
    {
        $count =  static::getModel()::where('is_active', false)->count();
        return $count > 0 ? (string) $count : null;
    }
}
