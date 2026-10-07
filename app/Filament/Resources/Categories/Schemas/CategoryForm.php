<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Products\RelationManagers\CategoryProductsRelationManager;
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Models\Catalog\Category;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use App\Filament\Schemas\Tabs\DescriptionTab;
use App\Filament\Schemas\Tabs\FaqTab;
use App\Filament\Schemas\Tabs\HowToTab;
use App\Filament\Schemas\Tabs\FooterTab;
use App\Filament\Schemas\Tabs\ImagesTab;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\Toggle;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        $store = Filament::getTenant();
        $languages = $store->activeLanguages();

        return $schema
            ->components([
                Tabs::make('category')
                    ->contained(false)
                    ->schema([
                        Tab::make(__('admin.common.tabs.content'))
                            ->icon(Heroicon::OutlinedPencilSquare)
                            ->schema([
                                Section::make(__('admin.catalog.categories.tabs.content.labels.main'))
                                    ->schema([
                                        Select::make('parent_id')
                                            ->options(fn (?Category $record) => $record 
                                                ? Category::categoryChoices($store->id)->except($record->descendants()->push($record)->pluck('id')->all())
                                                : Category::categoryChoices($store->id)
                                            )
                                            ->searchable()
                                            ->preload()
                                            ->label(__('admin.catalog.categories.tabs.content.labels.parent_id'))
                                            ->placeholder(__('admin.catalog.categories.tabs.content.labels.is_root'))
                                            ->helperText(__('admin.catalog.categories.tabs.content.helpers.parent_id')),
        
                                        Toggle::make('is_active')
                                            ->label(__('admin.catalog.categories.tabs.content.labels.is_active'))
                                            ->default(true),
                                        Toggle::make('show_in_facets')
                                            ->label(__('admin.catalog.categories.tabs.content.labels.show_in_facets'))
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
                        ImagesTab::make($store, $languages, ['type' => 'category']),
                        Tab::make('products')
                            ->label(__('admin.catalog.categories.tabs.products.label'))
                            ->icon(NavigationItem::Products->icon())
                            ->visible(fn(?Category $record) => $record !== null)
                            ->badge(fn (?Category $record) => $record?->products()->count() ?: null)
                            ->schema([
                                Livewire::make(CategoryProductsRelationManager::class, fn(?Category $record, ?EditCategory $livewire) => [
                                    'ownerRecord' => $record,
                                    'pageClass'   => $livewire::class,
                                ])
                            ]),
                    ]),
            ]);
    }
}
