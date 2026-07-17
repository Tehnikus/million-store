<?php

namespace App\Filament\Resources\BlogTags;

use App\Filament\Resources\BlogTags\Pages\CreateBlogTag;
use App\Filament\Resources\BlogTags\Pages\EditBlogTag;
use App\Filament\Resources\BlogTags\Pages\ListBlogTags;
use App\Filament\Resources\BlogTags\Schemas\BlogTagForm;
// use App\Filament\Resources\BlogTags\Schemas\BlogTagInfolist;
use App\Filament\Resources\BlogTags\Tables\BlogTagsTable;
use App\Models\BlogTag;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Filament\Support\NavigationGroup;

class BlogTagResource extends Resource
{
    protected static ?string $model = BlogTag::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHashtag;
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'name';
    protected static string | \UnitEnum | null $navigationGroup = NavigationGroup::Blog;

    public static function form(Schema $schema): Schema
    {
        return BlogTagForm::configure($schema);
    }

    // public static function infolist(Schema $schema): Schema
    // {
    //     return BlogTagInfolist::configure($schema);
    // }

    public static function table(Table $table): Table
    {
        return BlogTagsTable::configure($table);
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
            'index' => ListBlogTags::route('/'),
            'create' => CreateBlogTag::route('/create'),
            'edit' => EditBlogTag::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.blog.tags.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.blog.tags.model_label_singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.blog.tags.navigation_label');
    }

    // Fix search columns error on Filament global search
    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }
}
