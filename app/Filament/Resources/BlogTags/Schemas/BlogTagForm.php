<?php

namespace App\Filament\Resources\BlogTags\Schemas;

use App\Domain\Media\ImageType;
use App\Filament\Resources\BlogTags\Pages\EditBlogTag;
use App\Filament\Resources\Categories\RelationManagers\BlogTagPostsRelationManager;
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Models\Blog\BlogTag;
use Filament\Schemas\Schema;
use Filament\Facades\Filament;
use App\Filament\Schemas\Tabs\{DescriptionTab, FaqTab, HowToTab, FooterTab, ImagesTab};
use Filament\Schemas\Components\{Livewire, Section, Tabs, Tabs\Tab};
use Filament\Forms\Components\Toggle;
use Filament\Support\Icons\Heroicon;

class BlogTagForm
{
    public static function configure(Schema $schema): Schema
    {
        $store = Filament::getTenant()->load('storeSettings');
        $languages = $store->activeLanguages();

        return $schema
            ->components([
                Tabs::make('blog_tag')
                    ->schema([
                        Tab::make(__('admin.common.tabs.content'))
                            ->icon(Heroicon::OutlinedPencilSquare)
                            ->schema([
                                Toggle::make('is_active')
                                    ->label(__('admin.blog.tags.fields.is_active'))
                                    ->default(true),
                                Toggle::make('is_menu')
                                    ->label(__('admin.blog.tags.fields.is_menu'))
                                    ->default(false),
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
                                    ]),
                                
                            ]),

                        ImagesTab::make($store, $languages, ImageType::BlogTag),

                        Tab::make('posts')
                            ->label(__('admin.blog.posts.navigation_label'))
                            ->icon(NavigationItem::BlogPosts->icon())
                            ->visible(fn(?BlogTag $record) => $record !== null)
                            ->badge(fn(?BlogTag $record) => $record?->blogPosts()->count() ?: null)
                            ->schema([
                                Livewire::make(BlogTagPostsRelationManager::class, fn(?BlogTag $record, ?EditBlogTag $livewire) => [
                                    'ownerRecord' => $record,
                                    'pageClass' => $livewire::class,
                                ])
                            ]),
                    ])
                    ->contained(false),
            ]);
    }
}
