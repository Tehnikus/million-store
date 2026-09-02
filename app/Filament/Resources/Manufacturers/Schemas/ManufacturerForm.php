<?php

namespace App\Filament\Resources\Manufacturers\Schemas;

use App\Models\Catalog\Manufacturer;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use App\Filament\Schemas\Tabs\DescriptionTab;
use App\Filament\Schemas\Tabs\FaqTab;
use App\Filament\Schemas\Tabs\HowToTab;
use App\Filament\Schemas\Tabs\FooterTab;
use App\Filament\Schemas\Tabs\ImagesTab;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\Toggle;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class ManufacturerForm
{
    public static function configure(Schema $schema): Schema
    {
        $store = Filament::getTenant();
        $languages = $store->activeLanguages();

        return $schema
            ->components([
                Tabs::make('manufacturer')
                    ->schema([
                        Tab::make(__('admin.common.tabs.content'))
                            ->schema([
                                Select::make('parent_id')
                                    ->label(__('admin.catalog.manufacturers.fields.parent_id'))
                                    ->relationship(
                                        name: 'parent', // Function name in Manufacturer model
                                        titleAttribute: 'name',
                                        ignoreRecord: true,
                                        modifyQueryUsing: fn(Builder $query, $record) => $query->where('store_id', Filament::getTenant()->id)
                                        // ->whereNot('parent_id', $record?->id),
                                    )
                                    ->getOptionLabelFromRecordUsing(fn(Manufacturer $record) => $record->name)
                                    ->searchable()
                                    ->preload()
                                    ->default(null)
                                    ->columnSpanFull()
                                    ->placeholder(__('admin.catalog.manufacturers.fields.is_root'))
                                    ->helperText(__('admin.catalog.manufacturers.helpers.parent_id')),

                                Toggle::make('is_active')
                                    ->label(__('admin.catalog.manufacturers.fields.is_active'))
                                    ->default(true),
                                Toggle::make('show_in_facets')
                                    ->label(__('admin.catalog.manufacturers.fields.show_in_facets'))
                                    ->default(true),
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
                        ImagesTab::make($store, $languages, ['type' => 'manufacturer'])
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
