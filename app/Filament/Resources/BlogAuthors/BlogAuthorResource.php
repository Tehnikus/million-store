<?php

namespace App\Filament\Resources\BlogAuthors;

use App\Models\Blog\BlogAuthor;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Filament\Resources\BlogAuthors\Pages\CreateBlogAuthor;
use App\Filament\Resources\BlogAuthors\Pages\EditBlogAuthor;
use App\Filament\Resources\BlogAuthors\Pages\ListBlogAuthors;
use App\Filament\Resources\BlogAuthors\Schemas\BlogAuthorForm;
use App\Filament\Resources\BlogAuthors\Tables\BlogAuthorsTable;

use App\Filament\Support\AdminMenu\NavigationItem;
use App\Filament\Support\AdminMenu\HasCentralizedNavigation;

class BlogAuthorResource extends Resource
{
    protected static ?string $model = BlogAuthor::class;
    protected static ?string $recordTitleAttribute = 'name';
    protected static bool $isGloballySearchable = false;

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
    
    // Some repeating navigation methods in one place
    use HasCentralizedNavigation;
    protected static function getMenuConfig(): NavigationItem
    {
        return NavigationItem::BlogAuthors;
    }
}
