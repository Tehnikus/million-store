<?php

namespace App\Filament\Resources\Slugs;

use App\Filament\Resources\Slugs\Pages\CreateSlug;
use App\Filament\Resources\Slugs\Pages\EditSlug;
use App\Filament\Resources\Slugs\Pages\ListSlugs;
use App\Filament\Resources\Slugs\Schemas\SlugForm;
use App\Filament\Resources\Slugs\Tables\SlugsTable;
use App\Models\Seo\Slug;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

use App\Filament\Support\AdminMenu\NavigationItem;
use App\Filament\Support\AdminMenu\HasCentralizedNavigation;

class SlugResource extends Resource
{
    protected static ?string $model = Slug::class;
    protected static bool $isScopedToTenant = true;
    protected static ?string $recordTitleAttribute = 'slug';
    protected static bool $isGloballySearchable = false;


    public static function form(Schema $schema): Schema
    {
        return SlugForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SlugsTable::configure($table);
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
            'index' => ListSlugs::route('/'),
            'create' => CreateSlug::route('/create'),
            'edit' => EditSlug::route('/{record}/edit'),
        ];
    }

    // Skip on global search, no need to show slugs
    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }

    // Some repeating navigation methods in one place
    use HasCentralizedNavigation;
    protected static function getMenuConfig(): NavigationItem
    {
        return NavigationItem::Slugs;
    }
}
