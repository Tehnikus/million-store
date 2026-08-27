<?php

namespace App\Filament\Resources\Tags\Schemas;

use App\Filament\Schemas\Tabs\DescriptionTab;
use App\Filament\Schemas\Tabs\FaqTab;
use App\Filament\Schemas\Tabs\FooterTab;
use App\Filament\Schemas\Tabs\HowToTab;
use App\Filament\Schemas\Tabs\ImagesTab;
use Filament\Facades\Filament;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class TagForm
{
    public static function configure(Schema $schema): Schema
    {
        $store      = Filament::getTenant();
        $languages  = $store->activeLanguages();

        return $schema
            ->components([

                Tabs::make('tag')
                    ->schema([
                        Tab::make(__('admin.common.tabs.content'))
                            ->schema([

                                Group::make([

                                    Toggle::make('is_active')
                                        ->label(__('admin.catalog.tags.fields.is_active'))
                                        ->helperText(__('admin.catalog.tags.helpers.is_active'))
                                        ->default(true),

                                    Toggle::make('show_in_facets')
                                        ->label(__('admin.catalog.tags.fields.show_in_facets'))
                                        ->helperText(__('admin.catalog.tags.helpers.show_in_facets'))
                                        ->default(true),
                                ])
                                ->columnSpanFull(),

                                CodeEditor::make('inline_style')
                                    ->language(Language::Css)
                                    ->label(__('admin.catalog.tags.fields.inline_style'))
                                    ->helperText(__('admin.catalog.tags.helpers.inline_style')),

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
                        ImagesTab::make($store, $languages, ['type' => 'tag'])
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
