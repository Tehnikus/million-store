<?php

namespace App\Filament\Resources\Products\Schemas;
use App\Filament\Schemas\Tabs\ImagesTab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Section;

// Reusable description tabs
use App\Filament\Schemas\Tabs\DescriptionTab;
use App\Filament\Schemas\Tabs\FaqTab;
use App\Filament\Schemas\Tabs\FooterTab;
use App\Filament\Schemas\Tabs\HowToTab;

use App\Filament\Resources\Products\Schemas\CategoriesTab;
use App\Filament\Resources\Products\Schemas\ManufacturersTab;
use App\Filament\Resources\Products\Schemas\TagsTab;
use App\Filament\Resources\Products\Schemas\PricesTab;
use App\Filament\Resources\Products\Schemas\OptionsTab;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        $store      = Filament::getTenant();
        $languages  = $store->activeLanguages();
        $currencies = $store->activeCurrencies();
        $countries  = $store->activeCountries();

        return $schema
            ->components([
                // Global data
                Section::make(__('admin.catalog.products.fields.global_data'))
                    ->description(__('admin.catalog.products.helpers.global_data'))
                    ->schema([
                        ...collect($languages)->map(
                            fn($language) =>
                            TextInput::make("global_name.{$language->locale}")
                                ->required()
                                ->prefix($language->locale)
                                ->columnSpanFull()
                                ->placeholder(__('admin.catalog.products.fields.global_name'))
                                ->hiddenLabel()
                                ->helperText(__('admin.catalog.products.helpers.global_name')),
                        )->all(),
                        TextInput::make('sku')
                            ->label(__('admin.catalog.products.fields.sku'))
                            ->placeholder(__('admin.catalog.products.fields.sku'))
                            ->helperText(__('admin.catalog.products.helpers.sku')),

                    ])
                    ->collapsible()
                    ->collapsed(fn($operation) => $operation !== 'create')
                    ->columnSpanFull(),

                Tabs::make('product')
                    ->schema([
                        // Store-scoped and translatable data 
                        Tab::make(__('admin.common.tabs.content'))
                            ->schema([
                                // Store-scoped data
                                Group::make([
                                    Toggle::make('is_active')
                                        ->label(__('admin.catalog.products.fields.is_active'))
                                        ->helperText(__('admin.catalog.products.helpers.is_active')),
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
                                // ->relationship(name:'storeDescription')
                                ->statePath('description')
                                ->columnSpanFull(),
                            ]),

                        Tab::make(PricesTab::label())->schema(PricesTab::schema($store->id, $languages, $currencies)),
                        Tab::make(CategoriesTab::label())->schema(CategoriesTab::schema($store->id)),
                        Tab::make(ManufacturersTab::label())->schema(ManufacturersTab::schema($store->id)),
                        Tab::make(TagsTab::label())->schema(TagsTab::schema($store->id)),
                        Tab::make(OptionsTab::label())->schema(OptionsTab::schema($store->id, $languages, $currencies)),
                        Tab::make(AttributesTab::label())->schema(AttributesTab::schema($store->id, $languages)),
                        ImagesTab::make($store, $languages, ['type' => 'product']),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}