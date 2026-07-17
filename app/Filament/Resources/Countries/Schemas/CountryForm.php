<?php

namespace App\Filament\Resources\Countries\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Group;
use Filament\Schemas\Components\Fieldset;
use Filament\Forms\Components\Select;
use App\Models\Language;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
class CountryForm
{
    public static function configure(Schema $schema): Schema
    {
        $activeLanguages = Language::query()->where('is_active', true)->get();
        return $schema
            ->components([
                Fieldset::make(__('admin.countries.fields.name'))
                    ->schema([
                        ...Language::query()
                            ->where('is_active', true)
                            ->get()
                            ->map(
                                fn(Language $language) =>
                                // Group::make()
                                // ->schema([
                                //     TextInput::make('first_name'),
                                //     TextInput::make('last_name'),
                                // ])
                                // ->columns(2)
                                TextInput::make("name.{$language->locale}")
                                    ->required()
                                    ->columnSpanFull()
                                    ->hiddenLabel()
                                    ->required()
                                    ->prefix($language->locale)
                                    ->placeholder(__('admin.countries.fields.name') . " ({$language->name})")
                            )
                            ->all(),
                    ])
                    ->columnSpanFull(),

                // KeyValue::make('regions')
                //     ->columnSpanFull()
                //     ->label(__('admin.countries.fields.regions'))
                //     ->keyLabel(__('admin.countries.fields.iso_code'))
                //     ->valueLabel(__('admin.countries.fields.region'))
                //     ->addActionLabel(__('admin.countries.fields.add_region')),
                Fieldset::make(__('admin.countries.fields.regions'))
                    ->schema([
                        Repeater::make('regions')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make(__('admin.countries.fields.iso_code'))->width('1%'),
                                ...$activeLanguages->map(fn($language) => TableColumn::make($language->name))->all(),
                            ])
                            ->schema([
                                TextInput::make('iso_code')->required(),
                                ...$activeLanguages->map(fn($language) => TextInput::make("name.{$language->locale}")->required())->all(),
                            ])
                            ->addActionLabel(__('admin.countries.fields.add_region'))
                            ->reorderable(false)
                            ->compact()
                            ->columnSpanFull(),
                    ])
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

                Select::make('default_currency_id')
                    ->label(__('admin.countries.fields.default_currency_id'))
                    ->relationship('defaultCurrency', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                Toggle::make('is_eu_member')
                    ->default(false)
                    ->label(__('admin.countries.fields.is_eu_member'))
                    ->columnSpanFull()
                    // ->helperText(__('admin.countries.helpers.is_eu_member'))
                    ,

                Toggle::make('is_active')
                    ->default(true)
                    ->columnSpanFull()
                    ->label(__('admin.countries.fields.is_active')),
            ]);
    }
}
