<?php

namespace App\Filament\Resources\ProductReviews\Schemas;

use App\Models\Catalog\Product;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Schema;

class ProductReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('store_id')
                    ->default(Filament::getTenant()->id),
                Select::make('product_id')
                    ->label(__('admin.catalog.product_reviews.fields.product'))
                    ->relationship(
                        name: 'product',
                        titleAttribute: 'global_name',
                        // modifyQueryUsing: fn(Builder $query) => $query->where('store_id', Filament::getTenant()->id),
                    )
                    ->getOptionLabelFromRecordUsing(fn(Product $record) => $record->global_name)
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('locale')
                    ->label(__('admin.blog.comments.fields.locale'))
                    ->options(
                        Filament::getTenant()
                            ->languages()
                            ->wherePivot('is_active', true)
                            ->get()
                            ->pluck('name', 'locale')
                    )
                    ->required(),
                TextInput::make('author')
                    ->required()
                    ->label(__('admin.blog.comments.fields.author')),
                TextInput::make('author_email')
                    ->email()
                    ->label(__('admin.blog.comments.fields.email')),
                Textarea::make('reviewBody')
                    ->columnSpanFull()
                    ->rows(6)
                    ->label(__('admin.blog.comments.fields.body')),
                Repeater::make('positiveNotes')
                    ->simple(
                        Textarea::make('name')->rows(2)
                    )
                    ->maxItems(3)
                    ->default([])
                    ->columnSpanFull()
                    ->label(__('admin.catalog.product_reviews.fields.positive_notes'))
                    ->addActionLabel(__('admin.catalog.product_reviews.buttons.add_positive'))
                    ->addActionAlignment('left')
                    ->addAction(fn(Action $action) => $action->color('success')->icon('heroicon-o-plus')),
                Repeater::make('negativeNotes')
                    ->simple(
                        Textarea::make('name')->rows(2)
                    )
                    ->maxItems(3)
                    ->default([])
                    ->columnSpanFull()
                    ->label(__('admin.catalog.product_reviews.fields.negative_notes'))
                    ->addActionLabel(__('admin.catalog.product_reviews.buttons.add_negative'))
                    ->addActionAlignment('left')
                    ->addAction(fn(Action $action) => $action->color('success')->icon('heroicon-o-plus')),
                ToggleButtons::make('reviewRating')
                    ->required()
                    ->options([
                        '1' => '1',
                        '2' => '2',
                        '3' => '3',
                        '4' => '4',
                        '5' => '5',
                    ])
                    ->default('5')
                    ->label(__('admin.blog.comments.fields.rating'))
                    ->grouped()
                    ->columnSpanFull(),
                Toggle::make('is_approved')
                    ->label(__('admin.blog.comments.fields.is_approved'))
                    ->columnSpanFull(),
            ]);
    }
}
