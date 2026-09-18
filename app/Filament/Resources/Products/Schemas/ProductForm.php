<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\RelationManagers\ReviewsRelationManager;
use App\Filament\Schemas\Tabs\DescriptionTab;
use App\Filament\Schemas\Tabs\FaqTab;
use App\Filament\Schemas\Tabs\FooterTab;
use App\Filament\Schemas\Tabs\HowToTab;
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductReview;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        $store = Filament::getTenant();
        $languages = $store->activeLanguages();

        return $schema
            ->components([

                Tabs::make(__('admin.common.tabs.description'))
                    ->schema([
                        Tab::make(__('admin.common.tabs.content'))
                            ->schema([
                                Section::make()
                                    ->schema([
                                        FusedGroup::make(
                                                collect($languages)->map(
                                                    fn($language) =>
                                                    TextInput::make("global_name.{$language->locale}")
                                                        ->required()
                                                        ->prefix($language->locale)
                                                        ->columnSpanFull()
                                                        ->label(__('admin.catalog.products.fields.global_name'))
                                                        ->placeholder(__('admin.catalog.products.fields.global_name'))
                                                        ->hiddenLabel(),
                                                )->toArray(),
                                            )
                                            ->columnSpanFull()
                                            ->label(__('admin.catalog.products.fields.global_name'))
                                            ->helperText(__('admin.catalog.products.helpers.global_name')),
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
                                    ->statePath('description'),
                            ])
                            ->icon(Heroicon::PencilSquare)
                            ->label(__('admin.common.tabs.content')),
                        PlacementTab::make($store, $languages)
                            ->label(__('admin.catalog.products.tabs.placement'))
                            ->icon(NavigationItem::Categories->icon()),
                        PricesTab::make($store, $languages)
                            ->label(__('admin.catalog.products.tabs.prices'))
                            ->icon(NavigationItem::Currencies->icon()),
                        OptionsTab::make($store, $languages)
                            ->label(__('admin.catalog.products.tabs.options'))
                            ->icon(NavigationItem::Options->icon()),
                        AttributesTab::make($store, $languages)
                            ->label(__('admin.catalog.products.tabs.attributes'))
                            ->icon(NavigationItem::Attributes->icon()),
                        Tab::make('reviews')
                            ->label(__('admin.catalog.products.tabs.reviews'))
                            ->icon(NavigationItem::ProductReviews->icon())
                            ->visible(fn(?Product $record) => $record !== null)
                            ->badge(fn(?Product $record) => $record !== null ? ProductReview::where('product_id', $record->id)->where('store_id', $store->id)->count() : null)
                            ->schema([
                                Livewire::make(ReviewsRelationManager::class, fn(?Product $record, ?EditProduct $livewire) => [
                                    'ownerRecord' => $record,
                                    'pageClass'   => $livewire::class,
                                ])
                            ]),

                    ])
                    ->contained(false),
                    
            ]);
    }
}
