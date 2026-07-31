<?php

namespace App\Domain\Store\Actions;

use App\Models\Global\Currency;
use App\Models\Global\Language;
use App\Models\Global\Country;
use App\Models\Global\Store;
use Illuminate\Support\Facades\DB;

/**
 * A single entry point for creating a new store from scratch - currency,
 * language, country (each either selected from existing ones or created)
 * plus the Store itself, all in a single transaction
 *
 * Called from two Filament pages:
 * App\Filament\Pages\Tenancy\StoreWizard (when the user has no tenants at all)
 * App\Filament\Pages\CreateStoreWizard (the Store Wizard menu item)
 */
class RegisterStore
{
    public function handle(array $data): Store
    {
        return DB::transaction(function () use ($data) {
            $currency = $this->resolveCurrency($data['currency']);
            $language = $this->resolveLanguage($data['language'], $currency);
            $country  = $this->resolveCountry($data['country'], $currency, $language);
            $store    = $this->createStore($data['store']);

            // Bindings are made through the reverse HasMany relationships of the Store model (storeCurrencies(), storeLanguages(), storeCountries())
            // not via belongsToMany()->attach(). 
            // Technically, it would be possible to use attach(), but it's easier to set additional pivot fields (is_default, sort_order) with a single create() call, 
            // without a second update() on top of the pivot
            $store->storeCurrencies()->create([
                'currency_id' => $currency->id,
                'is_active'   => true,
                'sort_order'  => 0,
            ]);

            $store->storeLanguages()->create([
                'language_id' => $language->id,
                // The only language of a newly created store is always both default and active at the same time. 
                // There's nothing to compare it to, this isn't a case where "disable the rest" logic is needed, like in Language::booted() with is_active=false
                'is_default'  => true,
                'is_active'   => true,
                'sort_order'  => 0,
            ]);

            $store->storeCountries()->create([
                'country_id' => $country->id,
                'is_active'  => true,
                'sort_order' => 0,
            ]);

            return $store;
        });
    }

    /**
     * @param array{name: string, host: string, is_active?: bool}
     */
    private function createStore(array $data): Store
    {
        return Store::create([
            'name'      => $data['name'],
            'host'      => $data['host'],
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * @param array{mode: string, existing_id?: int, name?: string, iso_code?: string, sign?: string, rate?: float}
     */
    private function resolveCurrency(array $data): Currency
    {
        // 'existing'
        if (($data['mode'] ?? 'new') === 'existing') {
            return Currency::findOrFail($data['existing_id']);
        }

        // 'new' - if there are no currencies in the system at all, 
        // this one MUST become the global default (rate_default = true)
        // otherwise there is nothing to calculate the rate for other currencies. 
        // Currency::booted() already contains the logic "if rate_default=true, set rate=1 and uncheck the flag for all others" 
        // we just need to pass the correct flag, the model will handle the rest automatically via saving()
        $isFirstCurrencyEver = ! Currency::query()->exists();

        return Currency::create([
            'name'           => $data['name'],
            'iso_code'       => $data['iso_code'],
            'sign'           => $data['sign'],
            // If this is the first currency, we ignore the rate; it's always rate = 1.
            // If it's not the first, we take what the user entered (the rate relative to the current default currency)
            'rate'           => $isFirstCurrencyEver ? 1 : ($data['rate'] ?? 1),
            'decimal_places' => 2,
            'rate_default'   => $isFirstCurrencyEver,
            'is_active'      => true,
        ]);
    }

    /**
     * @param array{mode: string, existing_id?: int, name?: string, iso_code?: string, locale?: string, ts_config?: string, image?: string}
     */
    private function resolveLanguage(array $data, Currency $currency): Language
    {
        if (($data['mode'] ?? 'new') === 'existing') {
            return Language::findOrFail($data['existing_id']);
        }

        return Language::create([
            'name'      => $data['name'],
            'iso_code'  => $data['iso_code'],
            'locale'    => $data['locale'],
            'ts_config' => $data['ts_config'] ?? 'simple',
            'image'     => $data['image'] ?? null,

            // We intentionally DO NOT ask for this as a separate field in the wizard form -
            // the only default language for a newly created store
            // is tied to the currency from step 1. Nothing prevents you from later going
            // to the regular LanguageResource and changing it - this is just a starting
            // sensible default, not a hard business rule at the database level.
            'default_currency_id' => $currency->id,

            'is_active' => true,
        ]);
    }

    /**
     * @param array{mode: string, existing_id?: int, name?: string, iso_code?: string, phone_code?: string, is_eu_member?: bool}
     */
    private function resolveCountry(array $data, Currency $currency, Language $language): Country
    {
        if (($data['mode'] ?? 'new') === 'existing') {
            return Country::findOrFail($data['existing_id']);
        }

        return Country::create([
            // Country::name - a jsonb/translatable column of the form {"uk": "...", "pl": "..."}.
            // The wizard form collects ONLY ONE value - in the language
            // selected/created in step 2 (we currently only have one store language;
            // other locales will appear later via a regular CountryResource).
            // Therefore, we manually wrap the string in an array with a locale key,
            // rather than using $country->setTranslation('name', ..., ...) -
            // This is possible, but create([...]) with a pre-defined array is simpler and
            // doesn't require a separate save() afterwards.
            'name' => [$language->locale => $data['name']],

            'iso_code'   => $data['iso_code'],
            'phone_code' => $data['phone_code'] ?? null,
            'regions'    => [],
            'is_eu_member' => $data['is_eu_member'] ?? false,

            // Same logic as Language - we bind it to the currency from step 1
            // as a reasonable default for a newly created store.
            'default_currency_id' => $currency->id,

            'is_active' => true,
        ]);
    }
}