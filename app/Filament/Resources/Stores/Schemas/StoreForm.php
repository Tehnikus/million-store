<?php

namespace App\Filament\Resources\Stores\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Callout;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Illuminate\Database\Eloquent\Builder;

class StoreForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label(__('admin.stores.fields.name'))
                    ->helperText(new HtmlString(__('admin.stores.helpers.name'))),

                TextInput::make('host')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->label(__('admin.stores.fields.host'))
                    ->helperText(new HtmlString(__('admin.stores.helpers.host')))
                    ->prefix('https://'),

                Toggle::make('is_active')
                    ->label(__('admin.stores.fields.is_active'))
                    ->default(true),

                Section::make()
                    ->schema([

                        // Repeater with relationship to Store::storeLanguages(): belongsToMany
                        Repeater::make('storeLanguages') // relation name in Store::class model
                            ->relationship()
                            ->maxItems(fn (): int => \App\Models\Global\Language::where('is_active', true)->count())
                            ->reorderable()
                            ->orderColumn('sort_order')
                            ->table([
                                TableColumn::make(__('admin.stores.fields.languages'))->alignment('center'),
                                TableColumn::make(__('admin.languages.fields.is_active'))->alignment('center')->width('100px'),
                                TableColumn::make(__('admin.languages.fields.is_default'))->alignment('center')->width('100px'),
                            ])
                            ->addActionLabel(__('admin.stores.fields.add_language'))
                            ->hiddenLabel()
                            ->schema([
                                Select::make('language_id')
                                    ->relationship(
                                        name: 'language', 
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn (Builder $query) => $query->where('is_active', true) // Filter languages only where is_active = true
                                    )
                                    ->required()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),

                                // If is_default is switched to true, is_active is switched to true also and is made unchangeable
                                Toggle::make('is_active')
                                    ->disabled(fn (callable $get): bool => $get('is_default') === true) // Sets disabled state to true
                                    ->dehydrated(), // Forces to save input state to DB, even if it is disabled
                                
                                // If one toggle is switched to true, others are switched to false
                                Toggle::make('is_default')
                                    ->live() // Required to switch other is_default toggles 
                                    ->afterStateUpdated(function ($state, callable $get, callable $set, Toggle $component) {
                                        // If toggle state is true
                                        if ($state === true) {
                                            $set('is_active', true);
                                            $repeaterRows = $get('../../storeLanguages') ?? []; // traverse two levels up to get all repeater rows
                                            $currentPath  = $component->getStatePath();         // get curren toggle path string
                                            foreach ($repeaterRows as $key => $value) {
                                                // turn off all toggles that are not current row
                                                if (!str_contains($currentPath, $key)) {
                                                    $set("../../storeLanguages.{$key}.is_default", false);
                                                }
                                            }
                                        }
                                    }),
                            ]),

                        // Repeater with relationship to Store::storeCurrencies(): belongsToMany
                        Repeater::make('storeCurrencies') // relation name in Store::class model
                            ->relationship()
                            ->maxItems(fn (): int => \App\Models\Global\Currency::where('is_active', true)->count())
                            ->reorderable()
                            ->orderColumn('sort_order')
                            ->table([
                                TableColumn::make(__('admin.stores.fields.currencies'))->alignment('center'),
                                TableColumn::make(__('admin.currencies.fields.is_active'))->alignment('center')->width('100px'),
                            ])
                            ->hiddenLabel()
                            ->addActionLabel(__('admin.stores.fields.add_currency'))
                            ->schema([
                                Select::make('currency_id')
                                    ->relationship(
                                        name: 'currency', 
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn (Builder $query) => $query->where('is_active', true) // Filter currencies only where is_active = true
                                    )
                                    ->required()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                                Toggle::make('is_active')
                                
                            ]),

                        // Repeater with relationship to Store::storeCountries(): belongsToMany
                        Repeater::make('storeCountries') // relation name in Store::class model
                            ->relationship()
                            ->maxItems(fn (): int => \App\Models\Global\Country::where('is_active', true)->count())
                            ->reorderable()
                            ->orderColumn('sort_order')
                            ->table([
                                TableColumn::make(__('admin.stores.fields.countries'))->alignment('center'),
                                TableColumn::make(__('admin.countries.fields.is_active'))->alignment('center')->width('100px'),
                            ])
                            ->hiddenLabel()
                            ->addActionLabel(__('admin.stores.fields.add_country'))
                            ->schema([
                                Select::make('country_id')
                                    ->relationship(
                                        name: 'country', 
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn (Builder $query) => $query->where('is_active', true) // Filter countries only where is_active = true
                                    )
                                    ->required()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                                Toggle::make('is_active')
                                
                            ]),
                    ])
                ->columnSpanFull(),

                Callout::make(__('admin.stores.helpers.on_save_title'))
                    ->description(__('admin.stores.helpers.on_save_info'))
                    ->info()
                    ->visibleOn('create') // Show only on shop creation
                    ->columnSpanFull(),
            ]);
    }
}
