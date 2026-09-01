<?php

namespace App\Filament\Resources\ProductReviews;

use App\Filament\Resources\ProductReviews\Pages\CreateProductReview;
use App\Filament\Resources\ProductReviews\Pages\EditProductReview;
use App\Filament\Resources\ProductReviews\Pages\ListProductReviews;
use App\Filament\Resources\ProductReviews\Schemas\ProductReviewForm;
use App\Filament\Resources\ProductReviews\Tables\ProductReviewsTable;
use App\Filament\Support\AdminMenu\HasCachedNavigationBadge;
use App\Filament\Support\AdminMenu\HasCentralizedNavigation;
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Models\Catalog\ProductReview;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductReviewResource extends Resource
{
    protected static ?string $model = ProductReview::class;
    protected static bool $isGloballySearchable = false;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('product', 'parent');
    }

    public static function form(Schema $schema): Schema
    {
        return ProductReviewForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductReviewsTable::configure($table);
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
            'index'     => ListProductReviews::route('/'),
            'create'    => CreateProductReview::route('/create'),
            'edit'      => EditProductReview::route('/{record}/edit'),
        ];
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
        return NavigationItem::ProductReviews;
    }
    
    // Cached navigation badge
    use HasCachedNavigationBadge;
    protected static function computeNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('is_approved', false)->where('is_admin_reply', false)->count();
        return $count > 0 ? (string) $count : null;
    }
}
