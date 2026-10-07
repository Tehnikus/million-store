<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Filament\Support\AdminMenu\HasCentralizedNavigation;
use App\Models\Catalog\Product;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static bool $isScopedToTenant = false;
    protected static ?string $recordTitleAttribute = 'global_name';

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
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
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }

    // Global search columns list
    public static function getGloballySearchableAttributes(): array
    {
        return ['sku', 'global_name'];
    }

    // Some repeating navigation methods in one place
    use HasCentralizedNavigation;
    protected static function getMenuConfig(): NavigationItem
    {
        return NavigationItem::Products;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['descriptions']);
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return "{$record?->currentDescription()?->name} ({$record->global_name})";
    }

    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        if (!$record) {
            return null;
        }

        return "{$record->currentDescription()?->name} ({$record->global_name})";
    }
}
