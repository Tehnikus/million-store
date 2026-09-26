<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Filament\Resources\ProductReviews\Schemas\ProductReviewForm;
use App\Filament\Resources\ProductReviews\Tables\ProductReviewsTable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ReviewsRelationManager extends RelationManager
{
    protected static string $relationship = 'reviews';

    public function form(Schema $schema): Schema
    {
        return ProductReviewForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return ProductReviewsTable::configure($table)
            ->searchable(false)
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function getTableHeading(): string
    {
        return __('admin.catalog.products.tabs.reviews');
    }
}
