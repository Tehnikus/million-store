<?php

namespace App\Filament\Resources\BlogComments\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Schema;
use Filament\Facades\Filament;
use App\Models\BlogPost;
use Illuminate\Database\Eloquent\Builder;

class BlogCommentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('blog_post_id')
                    ->label(__('admin.blog.comments.fields.post'))
                    ->relationship(
                        name: 'blogPost',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn(Builder $query) => $query->where('store_id', Filament::getTenant()->id),
                    )
                    ->getOptionLabelFromRecordUsing(fn(BlogPost $record) => $record->name)
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
