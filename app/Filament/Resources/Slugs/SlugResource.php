<?php

namespace App\Filament\Resources\Slugs;

use App\Filament\Resources\Slugs\Pages\CreateSlug;
use App\Filament\Resources\Slugs\Pages\EditSlug;
use App\Filament\Resources\Slugs\Pages\ListSlugs;
use App\Filament\Resources\Slugs\Schemas\SlugForm;
use App\Filament\Resources\Slugs\Tables\SlugsTable;
use App\Models\Slug;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Filament\Support\NavigationGroup;

class SlugResource extends Resource
{
    protected static ?string $model = Slug::class;
    protected static bool $isScopedToTenant = true;
    protected static ?string $recordTitleAttribute = 'slug';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;
    protected static ?int $navigationSort = 3;
    protected static string | \UnitEnum | null $navigationGroup = NavigationGroup::Seo;


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

    public static function getModelLabel(): string
    {
        return __('admin.seo.slugs.model_label_singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.seo.slugs.navigation_label');
    }

    // Skip on global search, no need to show slugs
    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }
}
