<?php

namespace App\Filament\Resources\BlogComments;

use App\Models\Blog\BlogComment;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use App\Filament\Resources\BlogComments\Pages\CreateBlogComment;
use App\Filament\Resources\BlogComments\Pages\EditBlogComment;
use App\Filament\Resources\BlogComments\Pages\ListBlogComments;
use App\Filament\Resources\BlogComments\Schemas\BlogCommentForm;
use App\Filament\Resources\BlogComments\Tables\BlogCommentsTable;

use App\Filament\Support\AdminMenu\NavigationItem;
use App\Filament\Support\AdminMenu\HasCentralizedNavigation;


class BlogCommentResource extends Resource
{
    protected static ?string $model = BlogComment::class;
    protected static bool $isGloballySearchable = false;
    protected static bool $isScopedToTenant = false; // is filtered by blog post and does not relate to store_id in any way

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

    // Skip global search
    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }

    // Some repeating navigation methods in one place
    use HasCentralizedNavigation;
    protected static function getMenuConfig(): NavigationItem
    {
        return NavigationItem::BlogComments;
    }

}
