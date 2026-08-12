<?php

namespace App\Filament\Resources\FacetPages;

use App\Filament\Resources\FacetPages\Pages\CreateFacetPage;
use App\Filament\Resources\FacetPages\Pages\EditFacetPage;
use App\Filament\Resources\FacetPages\Pages\ListFacetPages;
use App\Filament\Resources\FacetPages\Schemas\FacetPageForm;
use App\Filament\Resources\FacetPages\Tables\FacetPagesTable;
use App\Filament\Support\AdminMenu\HasCentralizedNavigation;
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Models\Catalog\FacetPage;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class FacetPageResource extends Resource
{
    protected static ?string $model = FacetPage::class;
    protected static bool $isScopedToTenant = true;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return FacetPageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FacetPagesTable::configure($table);
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
            'index' => ListFacetPages::route('/'),
            'create' => CreateFacetPage::route('/create'),
            'edit' => EditFacetPage::route('/{record}/edit'),
        ];
    }

        // Global search columns list
    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    // Some repeating navigation methods in one place
    use HasCentralizedNavigation;
    protected static function getMenuConfig(): NavigationItem
    {
        return NavigationItem::FacetPages;
    }
}
