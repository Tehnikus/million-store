<?php

namespace App\Filament\Resources\StoreInfoPages\Schemas;
use Filament\Facades\Filament;
use App\Filament\Schemas\Tabs\DescriptionTab;
use App\Filament\Schemas\Tabs\FaqTab;
use App\Filament\Schemas\Tabs\HowToTab;
use App\Filament\Schemas\Tabs\FooterTab;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

class StoreInfoPageForm
{
    public static function configure(Schema $schema): Schema
    {
        $store = Filament::getTenant()->load(['languages']);
        $languages = $store->languages()->wherePivot('is_active', true)->get();

        return $schema
            ->components([
                Tabs::make('info_page')
                    ->schema([
                        Tab::make(__('admin.common.tabs.content'))
                            ->schema([
                                Toggle::make('is_active')
                                    ->label(__('admin.blog.posts.fields.is_active'))
                                    ->default(true)
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

                            ])
                    ])
                    ->columnSpanFull()
            ]);
    }
}
