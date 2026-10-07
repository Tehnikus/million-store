<?php

namespace App\Filament\Resources\BlogPosts\Schemas;

use App\Filament\Resources\BlogPosts\Pages\EditBlogPost;
use App\Filament\Resources\Products\RelationManagers\{BlogCommentsRelationManager, PostProductsRelationManager};
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Models\Blog\{BlogPost, BlogTag, BlogAuthor};
use App\Filament\Schemas\Tabs\{DescriptionTab, FaqTab, HowToTab, FooterTab, ImagesTab};
use Filament\Facades\Filament;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\{Livewire, Section, Tabs, Tabs\Tab};
use Filament\Forms\Components\{Toggle, Select};
use Filament\Support\Icons\Heroicon;
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
                    ->contained(false)
                    ->schema([

                        Tab::make(__('admin.common.tabs.content'))
                            ->icon(Heroicon::PencilSquare)
                            ->label(__('admin.common.tabs.content'))
                            ->schema([
                                Section::make(__('admin.blog.posts.tabs.content.labels.main'))
                                    ->description(__('admin.blog.posts.tabs.content.helpers.main'))
                                    ->collapsible()
                                    ->collapsed(fn($operation) => $operation !== 'create')
                                    ->schema([

                                        Toggle::make('is_active')
                                            ->label(__('admin.blog.posts.tabs.content.labels.is_active'))
                                            ->helperText(__('admin.blog.posts.tabs.content.helpers.is_active'))
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
                                            ->helperText(__('admin.blog.posts.tabs.content.helpers.tags')),
                                        Select::make('author_id')
                                            ->label(__('admin.blog.authors.model_label_singular'))
                                            ->helperText(__('admin.blog.posts.tabs.content.helpers.author'))
                                            ->relationship(
                                                name: 'author',
                                                titleAttribute: 'name',
                                                modifyQueryUsing: fn(Builder $query) => $query->where('store_id', Filament::getTenant()->id),
                                            )
                                            ->getOptionLabelFromRecordUsing(fn(BlogAuthor $record) => $record->name)
                                            ->searchable()
                                            ->preload(),
                                    ]),

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

                        ImagesTab::make($store, $languages, ['type' => 'blog_post']),

                        Tab::make('products')
                            ->label(__('admin.blog.posts.tabs.products.label'))
                            ->icon(NavigationItem::Products->icon())
                            ->visible(fn(?BlogPost $record) => $record !== null)
                            ->badge(fn (?BlogPost $record) => $record?->products()->count() ?: null)
                            ->schema([
                                Livewire::make(PostProductsRelationManager::class, fn(?BlogPost $record, ?EditBlogPost $livewire) => [
                                    'ownerRecord' => $record,
                                    'pageClass'   => $livewire::class,
                                ])
                            ]),

                        Tab::make('reviews')
                            ->label(__('admin.blog.posts.tabs.comments.label'))
                            ->icon(NavigationItem::BlogComments->icon())
                            ->visible(fn(?BlogPost $record) => $record !== null)
                            ->badge(fn (?BlogPost $record) => $record?->comments()->count() ?: null)
                            ->schema([
                                Livewire::make(BlogCommentsRelationManager::class, fn(?BlogPost $record, ?EditBlogPost $livewire) => [
                                    'ownerRecord' => $record,
                                    'pageClass'   => $livewire::class,
                                ])
                            ]),
                    ]),
            ]);
    }
}
