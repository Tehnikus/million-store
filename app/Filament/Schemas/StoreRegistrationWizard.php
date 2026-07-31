<?php

namespace App\Filament\Schemas;
use App\Models\Global\Country;
use App\Models\Global\Language;
use App\Models\Global\Currency;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard\Step;

class StoreRegistrationWizard
{
    public static function steps(): array
    {
        return [
            self::currencyStep(),
            self::languageStep(),
            self::countryStep(), 
            self::storeStep(),
        ];
    }

    private static function currencyStep(): Step
    {
        // Check if any currency exists or DB is blank right after install
        $hasCurrencies = (bool) Currency::query()->exists();

        return Step::make(__('admin.global.store_wizard.steps.currency'))
            ->schema([
                // Set statePath('currency') so everythins will be passed as $data['currency'][...] after $form->getState()
                // This structure is expected RegisterStore::handle()
                Group::make([
                    Radio::make('mode')
                        ->options([
                            'existing'  => __('admin.global.store_wizard.fields.existing_currency'),
                            'new'       => __('admin.global.store_wizard.fields.new_currency'),
                        ])
                        ->default($hasCurrencies == true ? 'existing' : 'new')
                        ->inline()
                        ->live() // Required to show/hide currency form creation reactively
                        ->hidden(!$hasCurrencies) // Hide if no currencies created yet - on clean install
                        ->hiddenLabel(),

                    Select::make('existing_id')
                        ->options(fn() => Currency::query()->where('is_active', true)->pluck('name', 'id'))
                        ->required()
                        ->visible(fn(Get $get) => $get('mode') === 'existing')
                        ->hiddenLabel(),

                    TextInput::make('name')
                        ->label(__('admin.currencies.fields.name'))
                        ->required()
                        ->maxLength(255)
                        ->visible(fn(Get $get) => $get('mode') === 'new'),

                    TextInput::make('iso_code')
                        ->label(__('admin.currencies.fields.iso_code'))
                        ->required()
                        ->maxLength(3)
                        ->unique('currencies', 'iso_code')
                        ->visible(fn(Get $get) => $get('mode') === 'new'),

                    TextInput::make('sign')
                        ->label(__('admin.currencies.fields.sign'))
                        ->required()
                        ->maxLength(10)
                        ->visible(fn(Get $get) => $get('mode') === 'new'),

                    TextInput::make('rate')
                        ->label(__('admin.currencies.fields.rate'))
                        ->numeric()
                        ->step(0.001)
                        ->default(1)
                        ->required()
                        // Exchange rate only visible if this is NOT first currency. First currency always has rate=1, see RegisterStore::resolveCurrency()
                        ->visible(fn(Get $get) => $get('mode') === 'new' && $hasCurrencies),

                ])->statePath('currency'),
            ]);
    }

    private static function languageStep(): Step
    {
        $hasLanguages = Language::query()->exists();

        return Step::make(__('admin.global.store_wizard.steps.language'))
            ->schema([
                Group::make([
                    Radio::make('mode')
                        ->options([
                            'existing' => __('admin.global.store_wizard.fields.existing_language'),
                            'new' => __('admin.global.store_wizard.fields.new_language'),
                        ])
                        ->default($hasLanguages ? 'existing' : 'new')
                        ->inline()
                        ->live()
                        ->hidden(!$hasLanguages)
                        ->hiddenLabel(),

                    Select::make('existing_id')
                        ->options(fn() => Language::query()->where('is_active', true)->pluck('name', 'id'))
                        ->required()
                        ->visible(fn(Get $get) => $get('mode') === 'existing')
                        ->hiddenLabel(),

                    TextInput::make('name')
                        ->label(__('admin.languages.fields.name'))
                        ->required()
                        ->maxLength(255)
                        ->visible(fn(Get $get) => $get('mode') === 'new'),

                    TextInput::make('iso_code')
                        ->label(__('admin.languages.fields.iso_code'))
                        ->required()
                        ->maxLength(5)
                        ->unique('languages', 'iso_code')
                        ->visible(fn(Get $get) => $get('mode') === 'new'),

                    TextInput::make('locale')
                        ->label(__('admin.languages.fields.locale'))
                        ->required()
                        ->maxLength(2)
                        ->visible(fn(Get $get) => $get('mode') === 'new'),

                    // Postgres ts_config dictionary
                    Select::make('ts_config')
                        ->label(__('admin.languages.fields.fulltext_search_language'))
                        ->options([
                            'simple'        => 'Simple',
                            'english'       => 'English',
                            'russian'       => 'Russian',
                            'german'        => 'German',
                            'french'        => 'French',
                            'spanish'       => 'Spanish',
                            'italian'       => 'Italian',
                            'portuguese'    => 'Portuguese',
                            'dutch'         => 'Dutch',
                            'danish'        => 'Danish',
                            'finnish'       => 'Finnish',
                            'hungarian'     => 'Hungarian',
                            'norwegian'     => 'Norwegian',
                            'swedish'       => 'Swedish',
                            'turkish'       => 'Turkish',
                        ])
                        ->default('simple')
                        ->required()
                        ->visible(fn(Get $get) => $get('mode') === 'new'),

                    // No default_currency_id, see RegisterStore::resolveLanguage()
                    // A simple text message about default currency
                    Callout::make()
                        ->description(fn(Get $get) => __('admin.global.store_wizard.messages.currency_language_hint', [
                            'currency' => self::currentCurrencyLabel($get),
                        ]))
                        ->info()
                        ->columnSpanFull()
                        ->visible(fn(Get $get) => $get('mode') === 'new'),

                ])->statePath('language'),
            ]);
    }

    /**
     * Currency name for hint in steps 2-3
     * Not used in form data itself, only for convenient hint
     */
    private static function currentCurrencyLabel(Get $get): string
    {
        // One '../' step up from Group('language') to the Wizard root 
        // (Step itself does not add nesting level)
        // Then 'currency.mode'/'currency.name' traverses to siebling Group('currency').
        if ($get('../currency.mode') === 'existing') {
            return Currency::find($get('../currency.existing_id'))?->name ?? '--';
        }

        return $get('../currency.name') ?: '-';
    }


    private static function countryStep(): Step
    {
        $hasCountries = Country::query()->exists();

        return Step::make(__('admin.global.store_wizard.steps.country'))
            ->schema([
                Group::make([

                    Radio::make('mode')
                        ->options([
                            'existing' => __('admin.global.store_wizard.fields.existing_country'),
                            'new' => __('admin.global.store_wizard.fields.new_country'),
                        ])
                        ->default($hasCountries ? 'existing' : 'new')
                        ->inline()
                        ->live()
                        ->hidden(!$hasCountries),

                    Select::make('existing_id')
                        
                        // Country.name - jsonb/translatable. 
                        // So pluck('name', 'id') will return a RAW JSON object as a string, not the text in the current locale
                        // Therefore, instead of pluck(), use get() + mapWithKeys(), where $country->name is already passed through the HasTranslations accessor.
                        ->options(fn() => Country::query()
                            ->where('is_active', true)
                            ->get()
                            ->mapWithKeys(fn(Country $country) => [$country->id => $country->name]))
                        ->required()
                        ->visible(fn(Get $get) => $get('mode') === 'existing')
                        ->hiddenLabel(),

                    // Only ONE locale - the one selected/created in step 2.
                    // RegisterStore::resolveCountry() wraps this value in {"locale": "..."} manually during create().
                    TextInput::make('name')
                        ->label(__('admin.countries.fields.name'))
                        ->required()
                        ->maxLength(255)
                        ->visible(fn(Get $get) => $get('mode') === 'new'),

                    TextInput::make('iso_code')
                        ->label(__('admin.countries.fields.iso_code'))
                        ->required()
                        ->maxLength(3)
                        ->unique('countries', 'iso_code')
                        ->visible(fn(Get $get) => $get('mode') === 'new'),

                    TextInput::make('phone_code')
                        ->label(__('admin.countries.fields.phone_code'))
                        ->maxLength(10)
                        ->visible(fn(Get $get) => $get('mode') === 'new'),

                    Toggle::make('is_eu_member')
                        ->label(__('admin.countries.fields.is_eu_member'))
                        ->default(false)
                        ->visible(fn(Get $get) => $get('mode') === 'new'),

                    // No default_currency_id, see RegisterStore::resolveCountry()
                    // A simple text message about default currency
                    Callout::make()
                        ->description(fn(Get $get) => __('admin.global.store_wizard.messages.currency_country_hint', [
                            'currency' => self::currentCurrencyLabel($get),
                        ]))
                        ->info()
                        ->columnSpanFull()
                        ->visible(fn(Get $get) => $get('mode') === 'new'),

                ])->statePath('country'),
            ]);
    }

    private static function storeStep(): Step
    {
        return Step::make(__('admin.global.store_wizard.steps.store'))
            ->schema([
                Group::make([

                    TextInput::make('name')
                        ->label(__('admin.stores.fields.name'))
                        ->required()
                        ->maxLength(255),

                    TextInput::make('host')
                        ->label(__('admin.stores.fields.host'))
                        ->required()
                        ->unique('stores', 'host')
                        ->prefix('https://')
                        ->maxLength(255),

                    Toggle::make('is_active')
                        ->label(__('admin.stores.fields.is_active'))
                        ->default(true)
                        ->live()
                        ->statePath('data.store.store.is_active'),

                ])->statePath('store'),
            ]);
    }
}