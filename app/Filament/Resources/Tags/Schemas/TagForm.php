<?php

namespace App\Filament\Resources\Tags\Schemas;

use App\Filament\Resources\Products\RelationManagers\TagProductsRelationManager;
use App\Filament\Resources\Tags\Pages\EditTag;
use App\Filament\Schemas\Tabs\DescriptionTab;
use App\Filament\Schemas\Tabs\FaqTab;
use App\Filament\Schemas\Tabs\FooterTab;
use App\Filament\Schemas\Tabs\HowToTab;
use App\Filament\Schemas\Tabs\ImagesTab;
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Models\Catalog\Tag;
use Filament\Facades\Filament;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class TagForm
{
    public static function configure(Schema $schema): Schema
    {
        $store      = Filament::getTenant();
        $languages  = $store->activeLanguages();

        return $schema
            ->components([

                Tabs::make('tag')
                    ->contained(false)
                    ->schema([
                        Tab::make(__('admin.common.tabs.content'))
                            ->icon(Heroicon::OutlinedPencilSquare)
                            ->schema([
                                Section::make(__('admin.catalog.tags.tabs.content.labels.main'))
                                    ->collapsible()
                                    ->collapsed(fn($operation) => $operation !== 'create')
                                    ->description(__('admin.catalog.tags.tabs.content.helpers.main'))
                                    ->schema([
                                        Group::make([
                                            Toggle::make('is_active')
                                                ->label(__('admin.catalog.tags.tabs.content.labels.is_active'))
                                                ->helperText(__('admin.catalog.tags.tabs.content.helpers.is_active'))
                                                ->default(true),
                                            Toggle::make('show_in_facets')
                                                ->label(__('admin.catalog.tags.tabs.content.labels.show_in_facets'))
                                                ->helperText(__('admin.catalog.tags.tabs.content.helpers.show_in_facets'))
                                                ->default(true),
                                        ])
                                        ->columnSpanFull(),
        
                                        CodeEditor::make('inline_style')
                                            ->language(Language::Css)
                                            ->label(__('admin.catalog.tags.tabs.content.labels.inline_style'))
                                            ->helperText(__('admin.catalog.tags.tabs.content.helpers.inline_style')),
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
                            ]),
                        ImagesTab::make($store, $languages, ['type' => 'tag']),
                        Tab::make('products')
                            ->label(__('admin.catalog.tags.tabs.products.label'))
                            ->icon(NavigationItem::Products->icon())
                            ->visible(fn(?Tag $record) => $record !== null)
                            ->badge(fn (?Tag $record) => $record?->products()->count() ?: null)
                            ->schema([
                                Livewire::make(TagProductsRelationManager::class, fn(?Tag $record, ?EditTag $livewire) => [
                                    'ownerRecord' => $record,
                                    'pageClass'   => $livewire::class,
                                ])
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
