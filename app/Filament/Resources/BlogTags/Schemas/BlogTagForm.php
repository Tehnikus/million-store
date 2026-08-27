<?php

namespace App\Filament\Resources\BlogTags\Schemas;

use Filament\Schemas\Schema;
use Filament\Facades\Filament;
use App\Filament\Schemas\Tabs\DescriptionTab;
use App\Filament\Schemas\Tabs\FaqTab;
use App\Filament\Schemas\Tabs\HowToTab;
use App\Filament\Schemas\Tabs\FooterTab;
use App\Filament\Schemas\Tabs\ImagesTab;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\Toggle;

class BlogTagForm
{
    public static function configure(Schema $schema): Schema
    {
        $store      = Filament::getTenant();
        $languages  = $store->activeLanguages();

        return $schema
            ->components([
                Tabs::make('blog_tag')
                    ->schema([
                        Tab::make(__('admin.common.tabs.content'))
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
                                    ])
                            ]),
                        ImagesTab::make($store, $languages, ['type' => 'blog_tag'])
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
