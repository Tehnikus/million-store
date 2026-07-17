<?php

namespace App\Filament\Resources\BlogComments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

use App\Models\BlogComment;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class BlogCommentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('blogPost.name')
                    ->limit(30)
                    ->searchable()
                    ->label(__('admin.blog.comments.fields.post'))
                    ->width('1%'),

                IconColumn::make('is_admin_reply')
                    ->label(__('admin.blog.comments.fields.type'))
                    ->icon(fn(bool $state) => $state ? 'heroicon-o-arrow-uturn-left' : 'heroicon-o-chat-bubble-left')
                    ->tooltip(fn(BlogComment $record) => $record->is_admin_reply
                        ? __('admin.blog.comments.labels.admin_reply')
                        : __('admin.blog.comments.labels.customer_comment'))
                    ->alignment(Alignment::Center)
                    ->width('1%')
                    ->color('primary'),

                TextColumn::make('body')
                    ->label(__('admin.blog.comments.fields.body'))
                    ->limit(60)
                    ->wrap()
                    ->formatStateUsing(function (BlogComment $record) {
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
                    ->label(__('admin.blog.comments.fields.is_approved'))
                    ->alignment(Alignment::Center)
                    ->width('1%'),

                TextColumn::make('created_at')
                    ->label(__('admin.blog.comments.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    // ->toggleable(isToggledHiddenByDefault: true)
                    ->width('1%')
                    ->alignment(Alignment::Center)
                    ->wrap(),
            ])
            ->defaultSort('created_at', 'asc')
            ->filters([
                TernaryFilter::make('is_approved')
                    ->label(__('admin.blog.comments.filters.is_approved')),

                SelectFilter::make('rating')
                    ->label(__('admin.blog.comments.filters.rating'))
                    ->options(['1' => '★☆☆☆☆', '2' => '★★☆☆☆', '3' => '★★★☆☆', '4' => '★★★★☆', '5' => '★★★★★']),

            ])

            ->recordActions([
                // Reply modal window
                Action::make('reply')
                    ->label(__('admin.blog.comments.actions.reply'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->visible(fn (BlogComment $record) => ! $record->is_admin_reply && $record->replies()->where('is_admin_reply', true)->doesntExist())
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('author_name')
                            ->label(__('admin.blog.comments.fields.reply_author'))
                            ->default(__('admin.blog.comments.labels.store_reply_author'))
                            ->required(),

                        Textarea::make('body')
                            ->label(__('admin.blog.comments.fields.reply_body'))
                            ->required()
                            ->rows(6),
                    ])
                    ->action(function (BlogComment $record, array $data) {
                        $record->replies()->create([
                            'blog_post_id' => $record->blog_post_id,
                            'author_name' => $data['author_name'],
                            'body' => $data['body'],
                            'is_admin_reply' => true,
                            'is_approved' => $record->is_approved,
                            'locale' => $record->locale,
                        ]);

                        Notification::make()
                            ->title(__('admin.blog.comments.notifications.reply_sent'))
                            ->success()
                            ->send();
                    })
                    ->iconButton(),

                EditAction::make()->iconButton(),
                DeleteAction::make()->iconButton(),
            ])

            ->groups([
                Group::make('thread_id')
                    ->label(__('admin.blog.comments.fields.thread'))
                    ->getTitleFromRecordUsing(function (BlogComment $record) {
                        $root = $record->parent_id ? $record->parent : $record;
                        return \Illuminate\Support\Str::limit($root->body ?? $root->author_name, 40);
                    })
                    ->getTitleFromRecordUsing(function (BlogComment $record) {
                        $root = $record->parent_id ? $record->parent : $record;
                        return $record->blogPost->name . ' - ' . \Illuminate\Support\Str::limit($root->body ?? $root->author_name, 40);
                    })
                    ->orderQueryUsing(
                        fn(Builder $query, string $direction) => $query
                            ->orderBy('blog_post_id', $direction)
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
