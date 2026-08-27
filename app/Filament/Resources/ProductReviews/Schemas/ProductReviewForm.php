<?php

namespace App\Filament\Resources\ProductReviews\Schemas;

use App\Models\Catalog\Product;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
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
                    ->label(__('admin.blog.comments.fields.post'))
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
                TextInput::make('author_name')
                    ->required()
                    ->label(__('admin.blog.comments.fields.author')),
                TextInput::make('author_email')
                    ->email()
                    ->label(__('admin.blog.comments.fields.email')),
                Textarea::make('body')
                    ->columnSpanFull()
                    ->rows(6)
                    ->label(__('admin.blog.comments.fields.body')),
                ToggleButtons::make('rating')
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
