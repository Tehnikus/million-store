<?php

namespace App\Filament\Resources\BlogPosts;

use App\Filament\Resources\BlogPosts\Pages\ManageBlogBostComments;
use App\Models\Blog\BlogPost;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Filament\Resources\BlogPosts\Pages\CreateBlogPost;
use App\Filament\Resources\BlogPosts\Pages\EditBlogPost;
use App\Filament\Resources\BlogPosts\Pages\ListBlogPosts;
use App\Filament\Resources\BlogPosts\Schemas\BlogPostForm;
use App\Filament\Resources\BlogPosts\Tables\BlogPostsTable;
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Filament\Support\AdminMenu\HasCentralizedNavigation;

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return BlogPostForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BlogPostsTable::configure($table)->modifyQueryUsing(fn ($query) => $query->with('blogTags'));
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
            'index'     => ListBlogPosts::route('/'),
            'create'    => CreateBlogPost::route('/create'),
            'edit'      => EditBlogPost::route('/{record}/edit'),
            'comments'  => ManageBlogBostComments::route('/{record}/comments')
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            EditBlogPost::class,
            ManageBlogBostComments::class,
        ]);
    }

    // Only search by name to avoid excessive overhead and search results bloat
    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    // Badge with count of posts that are NOT active. Nifty!
    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('is_active', false)->count();
        return $count > 0 ? (string) $count : null;
    }
    
    // Some repeating navigation methods in one place
    use HasCentralizedNavigation;
    protected static function getMenuConfig(): NavigationItem
    {
        return NavigationItem::BlogPosts;
    }
}
