<?php

namespace App\Filament\Pages;

use App\Domain\Ai\AiProvider;
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Filament\Support\AdminMenu\HasCentralizedNavigation;
use App\Models\Store\StoreInfoPage;
use App\Models\Store\StoreSettings as StoreSettingsModel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\{Hidden, Repeater, Repeater\TableColumn, Select, Textarea, TextInput, Toggle};
use Filament\Pages\Page;
use Filament\Schemas\Components\{Actions, Form, FusedGroup, Fieldset, Section, Tabs, Tabs\Tab};
use Filament\Schemas\Components\Utilities\{Get, Set};
use Filament\Schemas\Schema;

class StoreSettings extends Page
{
    protected string $view = 'filament.pages.simple-form';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->getRecord()?->toArray() ?? []);
    }

    public function getRecord(): ?StoreSettingsModel
    {
        $store = Filament::getTenant();

        return StoreSettingsModel::query()
            ->where('store_id', $store->id)
            ->first();
    }

    public function form(Schema $schema): Schema
    {

        $store      = Filament::getTenant();
        $languages  = $store->activeLanguages();
        $currencies = $store->activeCurrencies();        

        return $schema
            ->components([
                Form::make([
                    Tabs::make('settings_tabs')
                        ->tabs([

                            Tab::make(__('admin.store_settings.tabs.delivery_settings'))
                                ->icon(NavigationItem::Delivery->icon())
                                ->schema([
                                    TextInput::make('delivery_settings.transit_min')
                                        ->numeric()
                                        ->label(__('admin.store_settings.delivery_settings.fields.transit_min'))
                                        ->placeholder(__('admin.store_settings.delivery_settings.fields.transit_min'))
                                        ->helperText(__('admin.store_settings.delivery_settings.helpers.transit')),
                                    TextInput::make('delivery_settings.transit_max')
                                        ->numeric()
                                        ->label(__('admin.store_settings.delivery_settings.fields.transit_max'))
                                        ->placeholder(__('admin.store_settings.delivery_settings.fields.transit_max'))
                                        ->helperText(__('admin.store_settings.delivery_settings.helpers.transit')),
                                    TextInput::make('delivery_settings.handling_min')
                                        ->numeric()
                                        ->label(__('admin.store_settings.delivery_settings.fields.handling_min'))
                                        ->placeholder(__('admin.store_settings.delivery_settings.fields.handling_min'))
                                        ->helperText(__('admin.store_settings.delivery_settings.helpers.handling')),
                                    TextInput::make('delivery_settings.handling_max')
                                        ->numeric()
                                        ->label(__('admin.store_settings.delivery_settings.fields.handling_max'))
                                        ->placeholder(__('admin.store_settings.delivery_settings.fields.handling_max'))
                                        ->helperText(__('admin.store_settings.delivery_settings.helpers.handling')),
                                    TextInput::make('delivery_settings.return_cost')
                                        ->numeric()
                                        ->label(__('admin.store_settings.delivery_settings.fields.return_cost'))
                                        ->placeholder(__('admin.store_settings.delivery_settings.fields.return_cost'))
                                        ->helperText(__('admin.store_settings.delivery_settings.helpers.return_cost')),
                                ]),
                            Tab::make(__('admin.store_settings.tabs.checkout_settings'))
                                ->icon(NavigationItem::Payment->icon())
                                ->schema([
                                    Fieldset::make('admin.store_settings.checkout_settings.fields.minimal_order_total')
                                        ->schema(
                                            collect($currencies)->map(
                                                fn($currency) =>
                                                TextInput::make("checkout_settings.minimal_order_total.{$currency->iso_code}")
                                                    ->placeholder(__('admin.store_settings.checkout_settings.fields.minimal_order_total'))
                                                    ->required()
                                                    ->prefix($currency->sign)
                                                    ->hiddenLabel()
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->default(0)
                                                    ->inputMode('decimal')
                                                    ->required()
                                                    ->hiddenLabel()
                                            )->all()
                                        )
                                        ->label(__('admin.store_settings.checkout_settings.fields.minimal_order_total')),

                                    Fieldset::make(__('admin.store_settings.checkout_settings.fields.agreement_pages'))
                                        ->schema([
                                            Select::make('checkout_settings.service_agreement')
                                                ->options(fn () => StoreInfoPage::query()
                                                    ->where('store_id', $store->id)
                                                    ->where('is_active', true)
                                                    ->pluck('name', 'id')
                                                )
                                                ->searchable()
                                                ->preload()
                                                ->label(__('admin.store_settings.checkout_settings.fields.service_agreement_page'))
                                                ->helperText(__('admin.store_settings.checkout_settings.helpers.service_agreement_page')),

                                            Select::make('checkout_settings.return_agreement')
                                                ->options(fn () => StoreInfoPage::query()
                                                    ->where('store_id', $store->id)
                                                    ->where('is_active', true)
                                                    ->pluck('name', 'id')
                                                )
                                                ->searchable()
                                                ->preload()
                                                ->label(__('admin.store_settings.checkout_settings.fields.return_rules_page'))
                                                ->helperText(__('admin.store_settings.checkout_settings.helpers.return_rules_page'))
                                        ]),
                                    Fieldset::make(__('admin.store_settings.checkout_settings.fields.checkout_address_fields'))
                                        ->schema([
                                            // Address fields
                                            Repeater::make('checkout_settings.checkout_fields')
                                                ->table([
                                                    TableColumn::make(__('admin.store_settings.checkout_settings.fields.field_type'))->width('50%')->markAsRequired(),
                                                    TableColumn::make(__('admin.store_settings.checkout_settings.fields.field_name'))->width('50%')->markAsRequired(),
                                                    TableColumn::make(__('admin.store_settings.checkout_settings.fields.is_required'))->width('1%'),
                                                ])                                    
                                                ->schema([
                                                    Select::make('type')
                                                        ->options([
                                                            'country'     => __('admin.store_settings.checkout_settings.fields.country'),
                                                            'city'        => __('admin.store_settings.checkout_settings.fields.city'),
                                                            'street'      => __('admin.store_settings.checkout_settings.fields.street'),
                                                            'building'    => __('admin.store_settings.checkout_settings.fields.building'),
                                                            'apartment'   => __('admin.store_settings.checkout_settings.fields.apartment'),
                                                            'postal_code' => __('admin.store_settings.checkout_settings.fields.postal_code'),
                                                            'phone'       => __('admin.store_settings.checkout_settings.fields.phone'),
                                                            'company'     => __('admin.store_settings.checkout_settings.fields.company'),
                                                            'vat_number'  => __('admin.store_settings.checkout_settings.fields.vat_number'),
                                                            'region'      => __('admin.store_settings.checkout_settings.fields.region'),
                                                        ])
                                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                                        ->required(),
                                                    FusedGroup::make()
                                                        ->schema(
                                                            collect($languages)->map(
                                                                fn($language) =>
                                                                TextInput::make("label.{$language->locale}")
                                                                    ->required()
                                                                    ->prefix($language->locale)
                                                                    ->label(__('admin.store_settings.checkout_settings.fields.field_name'))
                                                                    ->placeholder(__('admin.store_settings.checkout_settings.fields.field_name'))
                                                                    ->hiddenLabel()
                                                            )->all()
                                                        ),
                                                        Toggle::make('is_required')
                                                            ->label(__('admin.store_settings.checkout_settings.fields.is_required'))
                                                ])
                                                ->reorderable()
                                                ->addActionLabel(__('admin.store_settings.checkout_settings.fields.add_field'))
                                                ->label(__('admin.store_settings.checkout_settings.fields.checkout_address_fields'))
                                                ->helperText(__('admin.store_settings.checkout_settings.helpers.checkout_fields')),
                                            
                                            // Additional custom fields
                                            Repeater::make('checkout_settings.custom_fields')
                                                ->table([
                                                    TableColumn::make(__('admin.store_settings.checkout_settings.fields.field_type'))->width('50%')->markAsRequired(),
                                                    TableColumn::make(__('admin.store_settings.checkout_settings.fields.field_name'))->width('50%')->markAsRequired(),
                                                    TableColumn::make(__('admin.store_settings.checkout_settings.fields.is_required'))->width('1%'),
                                                ])
                                                ->schema([
                                                    Select::make('type')
                                                        ->options([
                                                            'time'      => __('admin.store_settings.checkout_settings.fields.time'),
                                                            'date'      => __('admin.store_settings.checkout_settings.fields.date'),
                                                            'datetime'  => __('admin.store_settings.checkout_settings.fields.datetime'),
                                                            'text'      => __('admin.store_settings.checkout_settings.fields.text'),
                                                            'textarea'  => __('admin.store_settings.checkout_settings.fields.textarea'),
                                                            'checkbox'  => __('admin.store_settings.checkout_settings.fields.checkbox'),
                                                        ]),
                                                    Hidden::make('name'),
                                                    FusedGroup::make()
                                                        ->schema(
                                                            collect($languages)->map(
                                                                fn($language) =>
                                                                TextInput::make("label.{$language->locale}")
                                                                    ->required()
                                                                    ->prefix($language->locale)
                                                                    ->label(__('admin.store_settings.checkout_settings.fields.field_name'))
                                                                    ->placeholder(__('admin.store_settings.checkout_settings.fields.field_name'))
                                                                    ->hiddenLabel()
                                                            )->all()
                                                        ),
                                                        Toggle::make('is_required')
                                                            ->label(__('admin.store_settings.checkout_settings.fields.is_required'))
                                                ])
                                                ->reorderable()
                                                ->addActionLabel(__('admin.store_settings.checkout_settings.fields.add_field'))
                                                ->label(__('admin.store_settings.checkout_settings.fields.checkout_custom_fields'))
                                                ->helperText(__('admin.store_settings.checkout_settings.helpers.custom_fields')),
                                        ]),
                                ]),
                            Tab::make(__('admin.store_settings.tabs.legal_settings'))
                                ->icon(Heroicon::OutlinedShieldCheck)
                                ->schema([

                                ]),
                            Tab::make(__('admin.store_settings.tabs.tax_settings'))
                                ->icon(NavigationItem::Taxes->icon())
                                ->schema([

                                ]),
                            Tab::make(__('admin.store_settings.tabs.analytics_settings'))
                                ->icon(NavigationItem::Analytics->icon())
                                ->schema([

                                ]),
                            Tab::make(__('admin.store_settings.tabs.notification_settings'))
                                ->icon(NavigationItem::Notifications->icon())
                                ->schema([

                                ]),

                            Tab::make(__('admin.stores.store_settings.tabs.ai_settings.tab_label'))
                                ->icon(Heroicon::OutlinedSparkles)
                                ->schema([
                                    Section::make(__('admin.stores.store_settings.tabs.ai_settings.labels.prompt_settings'))
                                        ->description(__('admin.stores.store_settings.tabs.ai_settings.helpers.prompt_settings'))
                                        ->collapsible()
                                        ->schema([

                                            Repeater::make('ai_settings.prompts')
                                                ->table([
                                                    TableColumn::make(__('admin.stores.store_settings.tabs.ai_settings.columns.prompts'))->markAsRequired(),
                                                    TableColumn::make(__('admin.stores.store_settings.tabs.ai_settings.columns.is_default'))->width('140px'),
                                                ])
                                                ->schema([
                                                    FusedGroup::make([
                                                        TextInput::make('name')
                                                            ->required()
                                                            ->label(__('admin.stores.store_settings.tabs.ai_settings.labels.name'))
                                                            ->placeholder(__('admin.stores.store_settings.tabs.ai_settings.labels.name')),
                                                        Textarea::make('prompt')
                                                            ->required()
                                                            ->rows(6)
                                                            ->label(__('admin.stores.store_settings.tabs.ai_settings.labels.prompt'))
                                                            ->placeholder(__('admin.stores.store_settings.tabs.ai_settings.helpers.prompt')),
                                                    ]),
                                                    Toggle::make('is_default')
                                                        ->fixIndistinctState()

                                                ])
                                                ->hiddenLabel()
                                                ->label(__('admin.stores.store_settings.tabs.ai_settings.labels.prompt_settings'))
                                                ->addActionLabel(__('admin.stores.store_settings.tabs.ai_settings.buttons.add_prompt')),

                                        ]),

                                    Section::make(__('admin.stores.store_settings.tabs.ai_settings.labels.providers'))
                                        ->description(__('admin.stores.store_settings.tabs.ai_settings.helpers.providers'))
                                        ->collapsible()
                                        ->schema([

                                            Repeater::make('ai_settings.providers')
                                                ->table([
                                                    TableColumn::make(__('admin.stores.store_settings.tabs.ai_settings.columns.providers'))->markAsRequired()->width('240px'),
                                                    TableColumn::make(__('admin.stores.store_settings.tabs.ai_settings.columns.provider_settings'))->markAsRequired(),
                                                    TableColumn::make(__('admin.stores.store_settings.tabs.ai_settings.columns.is_default'))->width('140px'),
                                                ])
                                                ->schema([
                                                    FusedGroup::make([
                                                        Select::make('provider')
                                                            ->options(AiProvider::class)
                                                            ->live()
                                                            ->afterStateUpdated(function (mixed $state, Get $get, Set $set): void {
                                                                $provider = AiProvider::fromState($state);
                                                                if (!$provider) {
                                                                    return;
                                                                }

                                                                $current = trim((string) $get('endpoint'));

                                                                if ($current === '' || AiProvider::isDefaultEndpoint($current)) {
                                                                    $set('endpoint', $provider->defaultEndpoint());
                                                                }
                                                            })
                                                            ->required()
                                                            ->label(__('admin.stores.store_settings.tabs.ai_settings.labels.provider')),
                                                        TextInput::make('model')
                                                            ->required()
                                                            ->label(__('admin.stores.store_settings.tabs.ai_settings.labels.model')),
                                                    ]),
                                                    FusedGroup::make([
                                                        TextInput::make('api_key')
                                                            ->required()
                                                            ->label(__('admin.stores.store_settings.tabs.ai_settings.labels.api_key')),
                                                        TextInput::make('endpoint')
                                                            ->url()
                                                            ->label(__('admin.stores.store_settings.tabs.ai_settings.labels.endpoint'))
                                                    ]),
                                                    Toggle::make('is_default')
                                                        ->fixIndistinctState()

                                                ])
                                                ->hiddenLabel()
                                                ->label(__('admin.stores.store_settings.tabs.ai_settings.labels.providers'))
                                                ->addActionLabel(__('admin.stores.store_settings.tabs.ai_settings.buttons.add_provider')),
                                        ]),
                                ]),

                            Tab::make(__('admin.store_settings.tabs.maintenance_settings'))
                                ->icon(Heroicon::OutlinedWrenchScrewdriver)
                                ->schema([

                                ]),
                        ])
                        ->contained(false),
                ])
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->submit('save')->extraAttributes(['style' => 'min-width: 200px'])->label(__('admin.common.buttons.save')),
                    ]),
                ]),
            ])
            ->record($this->getRecord())
            ->statePath('data');
    }

    public function save(): void
    {
        $formData = $this->form->getState();                // Get form data
        $formData['store_id'] = Filament::getTenant()->id;  // Set form data store_id
        $formData = self::setCheckoutFieldsKeys($formData); // Set array keys to avoid input name collisions

        $record = $this->getRecord() ?? new StoreSettingsModel();
        $record->fill($formData);
        $record->save();

        $this->form->record($record);

        Notification::make()
            ->success()
            ->title(__('admin.messages.settings_saved'))
            ->send();
    }


    /**
     * Avoid name collisions on 
     * This two functions are needed to make custom fields names sequential and thus unique, so two fields of the same type can be added
     */
    protected function setCheckoutFieldsKeys(array $data): array
    {
        $data['checkout_settings']['custom_fields']   = self::assignCustomFieldKeys($data['checkout_settings']['custom_fields']   ?? []);
        $data['checkout_settings']['checkout_fields'] = self::assignCheckoutFieldKeys($data['checkout_settings']['checkout_fields'] ?? []);

        return $data;
    }

    private static function assignCheckoutFieldKeys(array $fields): array {
        foreach ($fields as $i => $field) {
            $fields[$i]['name'] = $field['type'];
        }
        return $fields;
    }
    
    /**
     * Generates a key only for new fields (those with an empty key)
     * Renaming the name of an existing field does NOT change the already assigned key.
     * Uniqueness is guaranteed by adding a numeric suffix in case of a collision.
     */
    private static function assignCustomFieldKeys(array $fields): array
    {
        $usedKeys = [];

        foreach ($fields as $i => $field) {
            $key = $field['name'] ?? null;

            if (blank($key)) {
                $base = Str::slug($field['type'] ?? 'field', '_');
                $base = $base !== '' ? $base : 'field';
            } else {
                $base = $key;
            }

            $candidate = $base;
            $suffix = 2;

            while (in_array($candidate, $usedKeys, true)) {
                $candidate = "{$base}_{$suffix}";
                $suffix++;
            }

            $usedKeys[] = $candidate;
            $fields[$i]['name'] = $candidate;
        }

        return $fields;
    }

    // Some repeating navigation methods in one place
    use HasCentralizedNavigation;
    protected static function getMenuConfig(): NavigationItem
    {
        return NavigationItem::StoreSettings;
    }
}