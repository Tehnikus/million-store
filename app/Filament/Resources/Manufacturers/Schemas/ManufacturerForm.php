<?php

namespace App\Filament\Resources\Manufacturers\Schemas;

use App\Filament\Resources\Manufacturers\Pages\EditManufacturer;
use App\Filament\Resources\Manufacturers\RelationManagers\ManufacturerProductsRelationManager;
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Models\Catalog\Manufacturer;
use Filament\Schemas\Components\{Livewire, Section};
use Filament\Schemas\Schema;
use Filament\Forms\Components\{Select, Toggle};
use App\Filament\Schemas\Tabs\{DescriptionTab, FaqTab, HowToTab, FooterTab, ImagesTab};
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;

class ManufacturerForm
{
    public static function configure(Schema $schema): Schema
    {
        $store = Filament::getTenant();
        $languages = $store->activeLanguages();

        return $schema
            ->components([
                Tabs::make('manufacturer')
                    ->contained(false)
                    ->schema([
                        Tab::make(__('admin.common.tabs.content'))
                            ->icon(Heroicon::OutlinedPencilSquare)
                            ->schema([
                                Section::make(__('admin.catalog.manufacturers.tabs.content.labels.main'))
                                    ->collapsible()
                                    ->collapsed(fn($operation) => $operation !== 'create')
                                    ->description(__('admin.catalog.manufacturers.tabs.content.helpers.main'))
                                    ->schema([
                                        Select::make('parent_id')
                                            ->options(fn (?Manufacturer $record) => $record 
                                                ? Manufacturer::manufacturerChoices($store->id)->except($record->descendants()->push($record)->pluck('id')->all())
                                                : Manufacturer::manufacturerChoices($store->id)
                                            )
                                            ->searchable()
                                            ->preload()
                                            ->label(__('admin.catalog.manufacturers.tabs.content.labels.parent_id'))
                                            ->placeholder(__('admin.catalog.manufacturers.tabs.content.labels.is_root'))
                                            ->helperText(__('admin.catalog.manufacturers.tabs.content.labels.parent_id')),
                                        Toggle::make('is_active')
                                            ->label(__('admin.catalog.manufacturers.tabs.content.labels.is_active'))
                                            ->default(true),
                                        Toggle::make('show_in_facets')
                                            ->label(__('admin.catalog.manufacturers.tabs.content.labels.show_in_facets'))
                                            ->default(true),
                                    ]),
                                Tabs::make('languages')
                                    ->schema([
                                        ...collect($languages)->map(
                                            fn($language) =>
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
                        ImagesTab::make($store, $languages, ['type' => 'manufacturer']),
                        Tab::make('products')
                            ->label(__('admin.catalog.manufacturers.tabs.products.label'))
                            ->icon(NavigationItem::Products->icon())
                            ->visible(fn(?Manufacturer $record) => $record !== null)
                            ->badge(fn (?Manufacturer $record) => $record?->products()->count() ?: null)
                            ->schema([
                                Livewire::make(ManufacturerProductsRelationManager::class, fn(?Manufacturer $record, ?EditManufacturer $livewire) => [
                                    'ownerRecord' => $record,
                                    'pageClass'   => $livewire::class,
                                ])
                            ]),
                    ]),
            ]);
    }
}
