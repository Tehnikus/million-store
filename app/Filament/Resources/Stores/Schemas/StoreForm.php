<?php

namespace App\Filament\Resources\Stores\Schemas;

use App\Models\Global\{Country, Currency, Language};
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Builder;

class StoreForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.global.stores.labels.main'))
                    ->description(__('admin.global.stores.helpers.main'))
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label(__('admin.global.stores.labels.name'))
                            ->placeholder(__('admin.global.stores.labels.name'))
                            ->helperText(new HtmlString(__('admin.global.stores.helpers.name'))),
        
                        TextInput::make('host')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->label(__('admin.global.stores.labels.host'))
                            ->placeholder(__('admin.global.stores.helpers.host_placeholder'))
                            ->helperText(new HtmlString(__('admin.global.stores.helpers.host')))
                            ->prefix('https://'),

                        Toggle::make('is_active')
                            ->label(__('admin.global.stores.labels.is_active'))
                            ->helperText(__('admin.global.stores.helpers.is_active'))
                            ->default(true),
                    ]),


                Section::make(__('admin.global.stores.labels.localization'))
                    ->description(__('admin.global.stores.helpers.localization'))
                    ->schema([

                        // Repeater with relationship to Store::storeLanguages(): belongsToMany
                        Repeater::make('storeLanguages') // relation name in Store::class model
                            ->relationship()
                            ->maxItems(fn (): int => Language::where('is_active', true)->count())
                            ->reorderable()
                            ->orderColumn('sort_order')
                            ->table([
                                TableColumn::make(__('admin.global.stores.labels.languages'))->alignment('center')->markAsRequired(),
                                TableColumn::make(__('admin.global.languages.fields.is_active'))->alignment('center')->width('150px'),
                                TableColumn::make(__('admin.global.languages.fields.is_default'))->alignment('center')->width('150px'),
                            ])
                            ->compact()
                            ->minItems(1)
                            ->hiddenLabel()
                            ->label(__('admin.global.stores.labels.languages'))
                            ->addActionLabel(__('admin.global.stores.labels.add_language'))
                            ->schema([
                                Select::make('language_id')
                                    ->relationship(
                                        name: 'language', 
                                        titleAttribute: 'name',
                                        // modifyQueryUsing: fn (Builder $query) => $query->where('is_active', true) // Filter languages only where is_active = true
                                    )
                                    ->required()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),

                                // If is_default is switched to true, is_active is switched to true also and is made unchangeable
                                Toggle::make('is_active')
                                    ->disabled(fn (callable $get): bool => $get('is_default') === true) // Sets disabled state to true
                                    ->dehydrated(true) // Forces to save input state to DB, even if it is disabled
                                    ->extraFieldWrapperAttributes(['style' => 'justify-self: center']),
                                
                                // If one toggle is switched to true, others are switched to false
                                Toggle::make('is_default')
                                    ->fixIndistinctState()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        // If toggle state is true
                                        if ($state === true) {
                                            $set('is_active', true);
                                        }
                                    })
                                    ->label(__('admin.global.languages.fields.is_default'))
                                    ->hiddenLabel()
                                    ->extraFieldWrapperAttributes(['style' => 'justify-self: center']),
                            ])
                            ->rules([fn () => function (string $attribute, $value, \Closure $fail) {
                                $activeCount  = collect($value)->filter(fn ($item) => ($item['is_active'] ?? null) === true)->count();
                                $defaultCount = collect($value)->filter(fn ($item) => ($item['is_default'] ?? null) === true)->count();
                                if ($activeCount < 1 || $defaultCount !== 1) {
                                    $fail(__('admin.global.stores.errors.languages'));
                                }
                            }])
                            ->validationMessages([
                                'required' => __('admin.global.stores.errors.languages'),
                                'min'      => __('admin.global.stores.errors.languages'),
                            ]),

                        // Repeater with relationship to Store::storeCurrencies(): belongsToMany
                        Repeater::make('storeCurrencies') // relation name in Store::class model
                            ->relationship()
                            ->maxItems(fn (): int => Currency::where('is_active', true)->count())
                            ->reorderable()
                            ->orderColumn('sort_order')
                            ->table([
                                TableColumn::make(__('admin.global.stores.labels.currencies'))->alignment('center')->markAsRequired(),
                                TableColumn::make(__('admin.global.currencies.fields.is_active'))->alignment('center')->width('300px'),
                            ])
                            ->compact()
                            ->minItems(1)
                            ->hiddenLabel()
                            ->label(__('admin.global.stores.labels.currencies'))
                            ->addActionLabel(__('admin.global.stores.labels.add_currency'))
                            ->schema([
                                Select::make('currency_id')
                                    ->relationship(
                                        name: 'currency', 
                                        titleAttribute: 'name',
                                        // modifyQueryUsing: fn (Builder $query) => $query->where('is_active', true) // Filter currencies only where is_active = true
                                    )
                                    ->required()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                                Toggle::make('is_active')
                                    ->label(__('admin.global.currencies.fields.is_active'))
                                    ->hiddenLabel()
                                    ->extraFieldWrapperAttributes(['style' => 'justify-self: center'])
                            ])
                            ->rules([fn () => function (string $attribute, $value, \Closure $fail) {
                                $activeCount  = collect($value)->filter(fn ($item) => ($item['is_active'] ?? null) === true)->count();
                                if ($activeCount < 1) {
                                    $fail(__('admin.global.stores.errors.currencies'));
                                }
                            }])
                            ->validationMessages([
                                'required' => __('admin.global.stores.errors.currencies'),
                                'min'      => __('admin.global.stores.errors.currencies'),
                            ]),

                        // Repeater with relationship to Store::storeCountries(): belongsToMany
                        Repeater::make('storeCountries') // relation name in Store::class model
                            ->relationship()
                            ->maxItems(fn (): int => Country::where('is_active', true)->count())
                            ->reorderable()
                            ->orderColumn('sort_order')
                            ->table([
                                TableColumn::make(__('admin.global.stores.labels.countries'))->alignment('center')->markAsRequired(),
                                TableColumn::make(__('admin.global.countries.fields.is_active'))->alignment('center')->width('300px'),
                            ])
                            ->compact()
                            ->minItems(1)
                            ->hiddenLabel()
                            ->label(__('admin.global.stores.labels.countries'))
                            ->addActionLabel(__('admin.global.stores.labels.add_country'))
                            ->schema([
                                Select::make('country_id')
                                    ->relationship(
                                        name: 'country', 
                                        titleAttribute: 'name',
                                        // modifyQueryUsing: fn (Builder $query) => $query->where('is_active', true) // Filter countries only where is_active = true
                                    )
                                    ->required()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                                Toggle::make('is_active')
                                    ->label(__('admin.global.countries.fields.is_active'))
                                    ->hiddenLabel()
                                    ->extraFieldWrapperAttributes(['style' => 'justify-self: center'])
                            ])
                            ->rules([fn () => function (string $attribute, $value, \Closure $fail) {
                                $activeCount  = collect($value)->filter(fn ($item) => ($item['is_active'] ?? null) === true)->count();
                                if ($activeCount < 1) {
                                    $fail(__('admin.global.stores.errors.countries'));
                                }
                            }])
                            ->validationMessages([
                                'required' => __('admin.global.stores.errors.countries'),
                                'min'      => __('admin.global.stores.errors.countries'),
                            ]),
                    ])
                ->columnSpanFull(),
            ]);
    }
}
