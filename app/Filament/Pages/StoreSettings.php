<?php

namespace App\Filament\Pages;

use App\Models\StoreSetting;
use App\Models\Currency;
use App\Filament\Support\NavigationGroup;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Support\Enums\Alignment;
use BackedEnum;

class StoreSettings extends Page
{
    protected string $view = 'filament.pages.simple-form';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;
    protected static ?int $navigationSort = 4;
    protected static string | \UnitEnum | null $navigationGroup = NavigationGroup::StoreSettings;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->getRecord()?->toArray() ?? []);
    }

    public function form(Schema $schema): Schema
    {

        // Get languages for inputs that require translatable values
        $languages = Filament::getTenant()->languages()->wherePivot('is_active', true)->get();
        // Get currencies for inputs that
        $currencies = Currency::get()->where('is_active', true);

        

        return $schema
            ->components([
                Form::make([
                    Tabs::make('settings_tabs')
                        ->tabs([

                            // Tab::make(__('admin.store_settings.tabs.image_settings'))
                            //     ->schema([
                            //         ...collect(ImageConversionType::cases())->flatMap(fn ($type) => [
                            //             Fieldset::make($type->label())
                            //                 ->schema([
                            //                     TextInput::make("image_dimensions.{$type->value}.width")
                            //                         ->numeric()
                            //                         ->required()
                            //                         ->label(__('admin.store_settings.image_settings.width')),
                            //                     TextInput::make("image_dimensions.{$type->value}.height")
                            //                         ->numeric()
                            //                         ->required()
                            //                         ->label(__('admin.store_settings.image_settings.height')),
                            //                 ])
                            //                 ->columns(2),
                            //         ])->all(),
                            //     ]),

                            Tab::make(__('admin.store_settings.tabs.delivery_settings'))
                                ->schema([
                                    TextInput::make('delivery_settings.transit_min')
                                        ->numeric()
                                        ->label(__('admin.store_settings.delivery_settings.fields.transit_min'))
                                        ->helperText(__('admin.store_settings.delivery_settings.helpers.transit')),
                                    TextInput::make('delivery_settings.transit_max')
                                        ->numeric()
                                        ->label(__('admin.store_settings.delivery_settings.fields.transit_max'))
                                        ->helperText(__('admin.store_settings.delivery_settings.helpers.transit')),
                                    TextInput::make('delivery_settings.handling_min')
                                        ->numeric()
                                        ->label(__('admin.store_settings.delivery_settings.fields.handling_min'))
                                        ->helperText(__('admin.store_settings.delivery_settings.helpers.handling')),
                                    TextInput::make('delivery_settings.handling_max')
                                        ->numeric()
                                        ->label(__('admin.store_settings.delivery_settings.fields.handling_max'))
                                        ->helperText(__('admin.store_settings.delivery_settings.helpers.handling')),
                                    TextInput::make('delivery_settings.return_cost')
                                        ->numeric()
                                        ->label(__('admin.store_settings.delivery_settings.fields.return_cost'))
                                        ->helperText(__('admin.store_settings.delivery_settings.helpers.return_cost')),
                                ]),
                            Tab::make(__('admin.store_settings.tabs.checkout_settings'))
                                ->schema([
                                    Fieldset::make('admin.store_settings.checkout_settings.fields.minimal_order_total')
                                        ->schema(
                                            collect($currencies)->map(
                                                fn($currency) =>
                                                TextInput::make("checkout_settings.minimal_order_total.{$currency->iso_code}")
                                                    ->required()
                                                    ->columnSpanFull()
                                                    ->prefix($currency->sign)
                                                    ->hiddenLabel()
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->default(0)
                                                    ->inputMode('decimal')
                                                    ->required()
                                            )->all()
                                        )
                                        ->label(__('admin.store_settings.checkout_settings.fields.minimal_order_total'))
                                        ->columnSpanFull(),

                                    Fieldset::make(__('admin.store_settings.checkout_settings.fields.agreement_pages'))
                                        ->schema([
                                            Select::make('checkout_settings.service_agreement')
                                                ->relationship(
                                                    name: 'infoPage',
                                                    titleAttribute: 'name',
                                                    modifyQueryUsing: fn(Builder $query) => $query->where('store_id', Filament::getTenant()->id)->where('is_active', true)
                                                )
                                                ->searchable()
                                                ->preload()
                                                ->columnSpanFull()
                                                ->label(__('admin.store_settings.checkout_settings.fields.service_agreement_page'))
                                                ->helperText(__('admin.store_settings.checkout_settings.helpers.service_agreement_page')),

                                            Select::make('checkout_settings.return_agreement')
                                                ->relationship(
                                                    name: 'infoPage',
                                                    titleAttribute: 'name',
                                                    modifyQueryUsing: fn(Builder $query) => $query->where('store_id', Filament::getTenant()->id)->where('is_active', true)
                                                )
                                                ->searchable()
                                                ->preload()
                                                ->columnSpanFull()
                                                ->label(__('admin.store_settings.checkout_settings.fields.return_rules_page'))
                                                ->helperText(__('admin.store_settings.checkout_settings.helpers.return_rules_page'))
                                        ]),
                                    Fieldset::make(__('admin.store_settings.checkout_settings.fields.checkout_fields'))
                                        ->schema([
                                            // Address fields
                                            Repeater::make('checkout_settings.checkout_fields')
                                                ->table([
                                                    TableColumn::make(__('admin.store_settings.checkout_settings.fields.field_type'))->width('50%'),
                                                    TableColumn::make(__('admin.store_settings.checkout_settings.fields.field_name'))->width('50%'),
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
                                                    Group::make()
                                                        ->schema(
                                                            collect($languages)->map(
                                                                fn($language) =>
                                                                TextInput::make("label.{$language->locale}")
                                                                    ->required()
                                                                    ->columnSpanFull()
                                                                    ->prefix($language->locale)
                                                            )->all()
                                                        ),
                                                        Toggle::make('is_required')
                                                            ->label(__('admin.store_settings.checkout_settings.fields.is_required'))
                                                ])
                                                ->label(__('admin.store_settings.checkout_settings.fields.checkout_address_fields'))
                                                ->addActionLabel(__('admin.store_settings.checkout_settings.fields.add_field'))
                                                ->reorderable()
                                                ->helperText(__('admin.store_settings.checkout_settings.helpers.checkout_fields'))
                                                ->columnSpanFull(),
                                            
                                            // Additional custom fields
                                            Repeater::make('checkout_settings.custom_fields')
                                                ->table([
                                                    TableColumn::make(__('admin.store_settings.checkout_settings.fields.field_type'))->width('50%'),
                                                    TableColumn::make(__('admin.store_settings.checkout_settings.fields.field_name'))->width('50%'),
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
                                                    Group::make()
                                                        ->schema(
                                                            collect($languages)->map(
                                                                fn($language) =>
                                                                TextInput::make("label.{$language->locale}")
                                                                    ->required()
                                                                    ->columnSpanFull()
                                                                    ->prefix($language->locale)
                                                            )->all()
                                                        ),
                                                        Toggle::make('is_required')
                                                            ->label(__('admin.store_settings.checkout_settings.fields.is_required'))
                                                ])
                                                ->label(__('admin.store_settings.checkout_settings.fields.checkout_custom_fields'))
                                                ->addActionLabel(__('admin.store_settings.checkout_settings.fields.add_field'))
                                                ->reorderable()
                                                ->helperText(__('admin.store_settings.checkout_settings.helpers.custom_fields'))
                                                ->columnSpanFull(),
                                        ]),
                                ]),
                            Tab::make(__('admin.store_settings.tabs.legal_settings'))
                                ->schema([

                                ]),
                            Tab::make(__('admin.store_settings.tabs.tax_settings'))
                                ->schema([

                                ]),
                            Tab::make(__('admin.store_settings.tabs.analytics_settings'))
                                ->schema([

                                ]),
                            Tab::make(__('admin.store_settings.tabs.notification_settings'))
                                ->schema([

                                ]),
                            Tab::make(__('admin.store_settings.tabs.maintenance_settings'))
                                ->schema([

                                ]),
                        ]),
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

        $record = $this->getRecord() ?? new StoreSetting();
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

    public function getRecord(): ?StoreSetting
    {
        $store = Filament::getTenant();

        return StoreSetting::query()
            ->where('store_id', $store->id)
            ->first();
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.items.store_settings');
    }

    public function getHeading(): string
    {
        return __('admin.navigation.items.store_settings');
    }

    public function getTitle(): string
    {
        return __('admin.navigation.items.store_settings');
    }
}