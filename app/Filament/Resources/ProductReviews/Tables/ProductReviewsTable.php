<?php

namespace App\Filament\Resources\ProductReviews\Tables;

use App\Models\Catalog\ProductReview;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class ProductReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.global_name')
                    ->limit(30)
                    ->searchable()
                    ->label(__('admin.catalog.product_reviews.fields.product'))
                    ->width('1%'),

                IconColumn::make('is_admin_reply')
                    ->label(__('admin.catalog.product_reviews.fields.type'))
                    ->icon(fn(bool $state) => $state ? 'heroicon-o-arrow-uturn-left' : 'heroicon-o-chat-bubble-left')
                    ->tooltip(fn(ProductReview $record) => $record->is_admin_reply
                        ? __('admin.catalog.product_reviews.labels.admin_reply')
                        : __('admin.catalog.product_reviews.labels.customer_review'))
                    ->alignment(Alignment::Center)
                    ->width('1%')
                    ->color('primary'),

                TextColumn::make('body')
                    ->label(__('admin.catalog.product_reviews.fields.body'))
                    ->limit(60)
                    ->wrap()
                    ->formatStateUsing(function (ProductReview $record) {
                        $stars = '';
                        if ($record->rating) {
                            $stars = '<div style="margin-bottom: 4px;">'
                                . str_repeat('<span style="color: #f59e0b;">★</span>', $record->rating)
                                . str_repeat('<span style="color: #d1d5db;">☆</span>', 5 - $record->rating)
                                . '</div>';
                        }

                        return new HtmlString(
                            '<div><strong>' . e($record->author_name) . '</strong></div>'
                            . $stars
                            . '<div class="text-gray-400">' . e($record->body) . '</div>'
                        );
                    }),

                ToggleColumn::make('is_approved')
                    ->label(__('admin.catalog.product_reviews.fields.is_approved'))
                    ->alignment(Alignment::Center)
                    ->width('1%'),

                TextColumn::make('created_at')
                    ->label(__('admin.catalog.product_reviews.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->width('1%')
                    ->alignment(Alignment::Center)
                    ->wrap(),
            ])
            ->defaultSort('created_at', 'asc')
            ->filters([
                TernaryFilter::make('is_approved')
                    ->label(__('admin.catalog.product_reviews.filters.is_approved')),

                SelectFilter::make('rating')
                    ->label(__('admin.catalog.product_reviews.filters.rating'))
                    ->options(['1' => '★☆☆☆☆', '2' => '★★☆☆☆', '3' => '★★★☆☆', '4' => '★★★★☆', '5' => '★★★★★']),

            ])

            ->recordActions([
                // Reply modal window
                Action::make('reply')
                    ->label(__('admin.catalog.product_reviews.actions.reply'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->visible(fn (ProductReview $record) => ! $record->is_admin_reply && $record->replies()->where('is_admin_reply', true)->doesntExist())
                    ->schema([
                        TextInput::make('author_name')
                            ->label(__('admin.catalog.product_reviews.fields.reply_author'))
                            ->default(Filament::getUserName(filament()->auth()->user()))
                            ->required(),

                        Textarea::make('body')
                            ->label(__('admin.catalog.product_reviews.fields.reply_body'))
                            ->required()
                            ->rows(6),
                    ])
                    ->action(function (ProductReview $record, array $data) {
                        $record->replies()->create([
                            'product_id'        => $record->product_id,
                            'author_name'       => $data['author_name'],
                            'body'              => $data['body'],
                            'is_admin_reply'    => true,
                            'is_approved'       => $record->is_approved,
                            'locale'            => $record->locale,
                            'store_id'          => Filament::getTenant()->id,
                        ]);

                        Notification::make()
                            ->title(__('admin.catalog.product_reviews.notifications.reply_sent'))
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])

            ->groups([
                Group::make('thread_id')
                    ->label(__('admin.catalog.product_reviews.fields.thread'))
                    // ->getTitleFromRecordUsing(function (ProductReview $record) {
                    //     $root = $record->parent_id ? $record->parent : $record;
                    //     return \Illuminate\Support\Str::limit($root->body ?? $root->author_name, 40);
                    // })
                    ->getTitleFromRecordUsing(function (ProductReview $record) {
                        $root = $record->parent_id ? $record->parent : $record;
                        return $record->product->global_name . ' - ' . \Illuminate\Support\Str::limit($root->body ?? $root->author_name, 40);
                    })
                    ->orderQueryUsing(
                        fn(Builder $query, string $direction) => $query
                            ->orderBy('product_id', $direction)
                            ->orderBy('thread_id', $direction)
                            ->orderByRaw('parent_id IS NULL DESC') //  parent (true) before reply (false)
                            ->orderBy('id')
                    ),
            ])
            // ->defaultGroup('thread_id')

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
