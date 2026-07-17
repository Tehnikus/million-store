<?php

namespace App\Filament\Resources\BlogPosts\Schemas;

use Filament\Schemas\Schema;
use Filament\Facades\Filament;
use App\Filament\Schemas\LanguageTabs;
use App\Filament\Schemas\Tabs\DescriptionTab;
use App\Filament\Schemas\Tabs\FaqTab;
use App\Filament\Schemas\Tabs\HowToTab;
use App\Filament\Schemas\Tabs\FooterTab;
use App\Filament\Schemas\Tabs\ImagesTab;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BlogTag;
use App\Models\BlogAuthor;

class BlogPostForm
{
    public static function configure(Schema $schema): Schema
    {
        $languages = Filament::getTenant()
            ->languages()
            ->wherePivot('is_active', true)
            ->get();
        return $schema
            ->components([
                Tabs::make('blog_tag')
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
                                LanguageTabs::make($languages, [
                                    [DescriptionTab::class, ['withSlug' => true]],
                                    FaqTab::class,
                                    HowToTab::class,
                                    FooterTab::class,

                                ])
                            ]),
                        Tab::make('images')
                            ->label(ImagesTab::label())
                            ->schema(ImagesTab::schema(['type' => 'blog']))
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
