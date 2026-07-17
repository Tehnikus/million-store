<?php

namespace App\Filament\Resources\BlogComments;

use App\Filament\Resources\BlogComments\Pages\CreateBlogComment;
use App\Filament\Resources\BlogComments\Pages\EditBlogComment;
use App\Filament\Resources\BlogComments\Pages\ListBlogComments;
use App\Filament\Resources\BlogComments\Schemas\BlogCommentForm;
use App\Filament\Resources\BlogComments\Tables\BlogCommentsTable;
use App\Models\BlogComment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

use App\Filament\Support\NavigationGroup;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class BlogCommentResource extends Resource
{
    protected static ?string $model = BlogComment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeft;

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $isScopedToTenant = false; // is filtered by blog post and does not relate to store_id in any way

    protected static string|\UnitEnum|null $navigationGroup = NavigationGroup::Blog;

    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('blogPost', fn(Builder $query) => $query->where('store_id', Filament::getTenant()->id))
            ->with('blogPost', 'parent');
    }

    public static function form(Schema $schema): Schema
    {
        return BlogCommentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BlogCommentsTable::configure($table);
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
            'index' => ListBlogComments::route('/'),
            'create' => CreateBlogComment::route('/create'),
            'edit' => EditBlogComment::route('/{record}/edit'),
        ];
    }

    // Badge with count of comments that are NOT approved. Nifty!
    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('is_approved', false)->where('is_admin_reply', false)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.blog.comments.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.blog.comments.model_label_singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.blog.comments.navigation_label');
    }

    // Skip global search
    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }

}
