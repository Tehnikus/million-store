<?php

namespace App\Filament\Resources\Countries\Schemas;

use App\Models\Global\Language;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;


class CountryForm
{

    public static function configure(Schema $schema): Schema
    {
        // Get all active languages instead of only stores active languages
        // because country can be used across stores with different set of languages
        // thus should have name in any possible lang accross all stores
        $activeLanguages = Language::query()->where('is_active', true)->get();

        return $schema
            ->components([
                Fieldset::make(__('admin.countries.fields.name'))
                    ->schema([
                        ...$activeLanguages
                            ->map(fn($language) =>
                                TextInput::make("name.{$language->locale}")
                                    ->required()
                                    ->columnSpanFull()
                                    ->required()
                                    ->prefix($language->locale)
                                    ->label(__('admin.countries.fields.name') . " ({$language->name})")
                                    ->placeholder(__('admin.countries.fields.name') . " ({$language->name})")
                            )
                            ->all(),
                    ])
                    ->columnSpanFull(),
                Fieldset::make(__('admin.countries.fields.localization_settings'))
                    ->schema([
                        Select::make('default_currency_id')
                            ->relationship(name: 'currency', titleAttribute: 'name')
                            ->searchable(['name', 'iso_code'])
                            ->required()
                            ->preload()
                            ->label(__('admin.countries.fields.default_currency_id'))
                            ->columnSpanFull(),
        
                        TextInput::make('iso_code')
                            ->required()
                            ->maxLength(3)
                            ->unique(ignoreRecord: true) // Needed to skip own record when checking unique
                            ->label(__('admin.countries.fields.iso_code'))
                            ->helperText(__('admin.countries.helpers.iso_code')),
        
                        TextInput::make('phone_code')
                            ->required()
                            ->maxLength(10)
                            ->label(__('admin.countries.fields.phone_code'))
                            ->helperText(__('admin.countries.helpers.phone_code')),
                    ])
                    ->columnSpanFull(),


                Fieldset::make(__('admin.countries.fields.regions'))
                    ->schema([
                        Repeater::make('regions')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make(__('admin.countries.fields.iso_code'))->width('200px'),
                                TableColumn::make(__('admin.countries.fields.region'))                                
                            ])
                            ->schema([
                                TextInput::make('iso_code')
                                    ->required()
                                    ->placeholder(__('admin.countries.fields.iso_code')),
                                Group::make([
                                    ...$activeLanguages->map(fn($language) => 
                                        TextInput::make("name.{$language->locale}")
                                            ->required()
                                            ->prefix($language->locale)
                                            ->placeholder(__('admin.countries.fields.name') . " ({$language->name})")
                                    )->all(),
                                ]),
                            ])
                            ->addActionLabel(__('admin.countries.fields.add_region'))
                            ->reorderable(false)
                            ->columnSpanFull()
                            ->defaultItems(0),
                    ])
                    ->columnSpanFull(),

                Toggle::make('is_eu_member')
                    ->default(false)
                    ->label(__('admin.countries.fields.is_eu_member'))
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->default(true)
                    ->columnSpanFull()
                    ->label(__('admin.countries.fields.is_active')),
            ]);
    }
}
