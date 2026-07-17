<?php

namespace App\Filament\Resources\BlogPosts;

use App\Filament\Resources\BlogPosts\Pages\CreateBlogPost;
use App\Filament\Resources\BlogPosts\Pages\EditBlogPost;
use App\Filament\Resources\BlogPosts\Pages\ListBlogPosts;
use App\Filament\Resources\BlogPosts\Schemas\BlogPostForm;
use App\Filament\Resources\BlogPosts\Tables\BlogPostsTable;
use App\Models\BlogPost;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Filament\Support\NavigationGroup;

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;
    protected static ?int $navigationSort = 1;
    protected static string | \UnitEnum | null $navigationGroup = NavigationGroup::Blog;
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
            'index' => ListBlogPosts::route('/'),
            'create' => CreateBlogPost::route('/create'),
            'edit' => EditBlogPost::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.blog.posts.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.blog.posts.model_label_singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.blog.posts.navigation_label');
    }

    // Fix search columns error on Filament global search
    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    // Badge with count of posts that are NOT active. Nifty!
    public static function getNavigationBadge(): ?string
    {
        $count =  static::getModel()::where('is_active', false)->count();
        return $count > 0 ? (string) $count : null;
    }
}
