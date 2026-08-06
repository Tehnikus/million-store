<?php

namespace App\Filament\Resources\Options;

use App\Filament\Resources\Options\Pages\CreateOption;
use App\Filament\Resources\Options\Pages\EditOption;
use App\Filament\Resources\Options\Pages\ListOptions;
use App\Filament\Resources\Options\Schemas\OptionForm;
use App\Filament\Resources\Options\Tables\OptionsTable;
use App\Filament\Support\AdminMenu\HasCentralizedNavigation;
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Models\Catalog\Option;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OptionResource extends Resource
{
    protected static ?string $model = Option::class;
    protected static bool $isScopedToTenant = true;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return OptionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OptionsTable::configure($table);
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
            'index' => ListOptions::route('/'),
            'create' => CreateOption::route('/create'),
            'edit' => EditOption::route('/{record}/edit'),
        ];
    }

    // Global search columns list
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'values.name']; // Also search values by hasMany relation
    }

    // Eager load on global search
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['values']);
    }

    // Some repeating navigation methods in one place
    use HasCentralizedNavigation;
    protected static function getMenuConfig(): NavigationItem
    {
        return NavigationItem::Options;
    }
}
