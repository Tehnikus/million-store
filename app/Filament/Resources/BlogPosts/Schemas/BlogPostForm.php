<?php

namespace App\Filament\Resources\BlogPosts\Schemas;

use App\Models\Blog\BlogTag;
use App\Models\Blog\BlogAuthor;
use App\Filament\Schemas\Tabs\DescriptionTab;
use App\Filament\Schemas\Tabs\FaqTab;
use App\Filament\Schemas\Tabs\HowToTab;
use App\Filament\Schemas\Tabs\FooterTab;
use App\Filament\Schemas\Tabs\ImagesTab;
use Filament\Facades\Filament;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;


class BlogPostForm
{
    public static function configure(Schema $schema): Schema
    {
        $store = Filament::getTenant();
        $languages = $store->activeLanguages();

        return $schema
            ->components([
                Tabs::make('blog_post')
                    ->schema([

                        Tab::make(__('admin.common.tabs.content'))
                            ->schema([
                                Toggle::make('is_active')
                                    ->label(__('admin.blog.posts.fields.is_active'))
                                    ->default(true),
                                // Relation of blog post to blog tags
                                Select::make('blog_tags')
                                    ->label(__('admin.blog.tags.navigation_label'))
                                    ->relationship(
                                        name: 'blogTags', // Function name in BlogTags
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn(Builder $query) => $query->where('store_id', Filament::getTenant()->id),
                                    )
                                    ->getOptionLabelFromRecordUsing(fn(BlogTag $record) => $record->name)
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->columnSpanFull()
                                    ->helperText(__('admin.blog.posts.helpers.tags')),
                                Select::make('author_id')
                                    ->label(__('admin.blog.authors.model_label_singular'))
                                    ->relationship(
                                        name: 'author',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn(Builder $query) => $query->where('store_id', Filament::getTenant()->id),
                                    )
                                    ->getOptionLabelFromRecordUsing(fn(BlogAuthor $record) => $record->name)
                                    ->searchable()
                                    ->preload()
                                    ->columnSpanFull(),

                                        Tabs::make('languages')
                                            ->schema([
                                                ...collect($languages)->map(fn($language) =>
                                                    Tab::make($language->locale)
                                                        ->label("{$language->name}")
                                                        ->schema([
                                                            Tabs::make("content.{$language->locale}")
                                                                ->schema([
                                                                    DescriptionTab::make($language, ['withSlug' => true]),
                                                                    FaqTab::make($language),
                                                                    HowToTab::make($language),
                                                                    FooterTab::make($language),
                                                                ])

                                                        ])
                                                )
                                            ])

                            ]),
                        ImagesTab::make($store, $languages, ['type' => 'blog_post'])
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
