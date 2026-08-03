<?php

namespace App\Filament\Resources\Tags\Schemas;

use App\Filament\Schemas\LanguageTabs;
use App\Filament\Schemas\Tabs\DescriptionTab;
use App\Filament\Schemas\Tabs\FaqTab;
use App\Filament\Schemas\Tabs\FooterTab;
use App\Filament\Schemas\Tabs\HowToTab;
use App\Filament\Schemas\Tabs\ImagesTab;
use Filament\Facades\Filament;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class TagForm
{
    public static function configure(Schema $schema): Schema
    {
        $languages = Filament::getTenant()->languages()->wherePivot('is_active', true)->get();
        return $schema
            ->components([

                Tabs::make('tag')
                    ->schema([
                        Tab::make(__('admin.common.tabs.content'))
                            ->schema([
                                
                                Toggle::make('is_active')
                                    ->label(__('admin.catalog.tags.fields.is_active'))
                                    ->default(true),
                                Toggle::make('show_in_facets')
                                    ->label(__('admin.catalog.tags.fields.show_in_facets'))
                                    ->default(true),
                                    
                                LanguageTabs::make($languages, [
                                    [DescriptionTab::class, ['withSlug' => true]],
                                    FaqTab::class,
                                    HowToTab::class,
                                    FooterTab::class,

                                ])
                            ]),
                        Tab::make('images')
                            ->label(ImagesTab::label())
                            ->schema(ImagesTab::schema(['type' => 'tag']))
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
