<?php

namespace App\Filament\Resources\BlogAuthors;

use App\Filament\Resources\BlogAuthors\Pages\CreateBlogAuthor;
use App\Filament\Resources\BlogAuthors\Pages\EditBlogAuthor;
use App\Filament\Resources\BlogAuthors\Pages\ListBlogAuthors;
use App\Filament\Resources\BlogAuthors\Schemas\BlogAuthorForm;
use App\Filament\Resources\BlogAuthors\Tables\BlogAuthorsTable;
use App\Models\BlogAuthor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Filament\Support\NavigationGroup;

class BlogAuthorResource extends Resource
{
    protected static ?string $model = BlogAuthor::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;
    protected static ?int $navigationSort = 3;
    protected static string | \UnitEnum | null $navigationGroup = NavigationGroup::Blog;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return BlogAuthorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BlogAuthorsTable::configure($table);
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
            'index' => ListBlogAuthors::route('/'),
            'create' => CreateBlogAuthor::route('/create'),
            'edit' => EditBlogAuthor::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('admin.blog.authors.model_label_singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.blog.authors.navigation_label');
    }

    // Skip global search for blog authors
    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }
}
