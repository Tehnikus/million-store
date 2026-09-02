<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Component;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Filament\Facades\Filament;
use App\Models\Customer\CustomerGroup;
use App\Models\Store\StoreSettings;
use App\Models\Global\Country;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        // Get checkout fields store settings
        $storeId            = Filament::getTenant()->id;
        $checkoutSettings   = StoreSettings::where('store_id', $storeId)->value('checkout_settings') ?? [];
        $checkoutFields     = $checkoutSettings['checkout_fields'] ?? [];
        $customFields       = $checkoutSettings['custom_fields'] ?? [];

        return $schema
            ->components([
                // Select::make('store_id')
                //     ->relationship('store', 'name')
                //     ->required(),
                Toggle::make('is_approved')
                    ->default(true)
                    ->label(__('admin.customers.customer.fields.is_approved')),
                Fieldset::make(__('admin.customers.customer.fields.presonal_data'))
                    ->schema([
                        TextInput::make('first_name')
                            ->required()
                            ->columnSpanFull()
                            ->placeholder(__('admin.customers.customer.fields.first_name'))
                            ->label(__('admin.customers.customer.fields.first_name')),
                        TextInput::make('last_name')
                            ->required()
                            ->columnSpanFull()
                            ->placeholder(__('admin.customers.customer.fields.last_name'))
                            ->label(__('admin.customers.customer.fields.last_name')),
                        TextInput::make('phone')
                            ->required()
                            ->tel()
                            ->columnSpanFull()
                            ->placeholder(__('admin.customers.customer.fields.phone'))
                            ->label(__('admin.customers.customer.fields.phone')),
                        DateTimePicker::make('created_at')
                            ->dehydrated(false)
                            ->disabled()
                            ->label(__('admin.customers.customer.fields.created_at')),
                        DateTimePicker::make('updated_at')
                            ->dehydrated(false)
                            ->disabled()
                            ->label(__('admin.customers.customer.fields.updated_at')),
                    ])
                    ->columnSpanFull(),
                Fieldset::make(__('admin.customers.customer.fields.addresses'))
                    ->schema([
                        // User addresses editor
                        Repeater::make('addresses')
                            ->schema([
                                // Address name given by customer, e.g. "Home", "Work", etc.
                                TextInput::make('label')
                                    ->placeholder(__('admin.customers.customer.fields.addresses_placeholder'))
                                    ->maxLength(100)
                                    ->live(onBlur: true)
                                    ->label(__('admin.customers.customer.fields.addresses_label')),

                                // Build address fields from store settings
                                ...self::buildFixedFields($checkoutFields), // General checkout fields: country, city, street, etc.
                                ...self::buildCustomFields($customFields),  // Additional custom fields: delivery time, some comments...

                                // Toggle for default delivery address
                                Toggle::make('is_default_shipping')
                                    ->label(__('admin.customers.customer.fields.is_default_shipping'))
                                    ->live()
                                    ->distinct()
                                    ->fixIndistinctState(),

                                Toggle::make('is_default_billing')
                                    ->label(__('admin.customers.customer.fields.is_default_billing'))
                                    ->live()
                                    ->distinct()
                                    ->fixIndistinctState(),
                            ])
                            ->itemLabel(fn(array $state): ?string => $state['label'] ?? null)
                            ->addActionLabel(__('admin.customers.customer.fields.add_address'))
                            ->collapsible()
                            ->reorderable(false)
                            ->defaultItems(0)
                            ->columnSpanFull()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                            ->collapsed(),
                    ])
                    ->columnSpanFull(),
                Fieldset::make(__('admin.customers.customer.fields.store_data'))
                    ->schema([

                        // Customer group with autocomplete
                        Select::make('customer_group_id')
                            ->required()
                            ->relationship(
                                name: 'group',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn(Builder $query) => $query->where('store_id', Filament::getTenant()->id),
                            )
                            ->getOptionLabelFromRecordUsing(fn(CustomerGroup $record) => $record->name)
                            ->searchable()
                            ->preload()
                            ->default(fn () => CustomerGroup::query()
                                ->where('store_id', $storeId)
                                ->where('is_default', true)
                                ->value('id')
                            )
                            ->label(__('admin.customers.customer.fields.customer_group_id'))
                            ->columnSpanFull(),

                        // Customer language
                        Select::make('locale')
                            ->options(
                                Filament::getTenant()
                                    ->languages()
                                    ->wherePivot('is_active', true)
                                    ->get()
                                    ->pluck('name', 'locale')
                            )
                            ->required()
                            ->columnSpanFull()
                            ->label(__('admin.customers.customer.fields.locale')),

                        // Password
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required(fn(string $operation) => $operation === 'create')
                            ->dehydrated(fn(?string $state) => filled($state))
                            ->dehydrateStateUsing(fn(?string $state) => filled($state) ? Hash::make($state) : null)
                            ->placeholder(__('admin.customers.customer.fields.password'))
                            ->label(__('admin.customers.customer.fields.password'))
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Fieldset::make(__('admin.customers.customer.fields.email_data'))
                    ->schema([
                        TextInput::make('email')
                            ->email()
                            ->placeholder(__('admin.customers.customer.fields.email'))
                            ->label(__('admin.customers.customer.fields.email'))                            ,
                        DateTimePicker::make('email_verified_at')
                            ->dehydrated(false)
                            ->disabled()
                            ->label(__('admin.customers.customer.fields.email_verified_at')),
                        Toggle::make('marketing_opt_in')
                            ->default(false)
                            ->label(__('admin.customers.customer.fields.marketing_opt_in')),
                    ])
                    ->columnSpanFull(),
                Fieldset::make(__('admin.customers.customer.fields.company_data'))
                    ->schema([
                        TextInput::make('company_name')
                            ->placeholder(__('admin.customers.customer.fields.company_name'))
                            ->label(__('admin.customers.customer.fields.company_name')),
                        TextInput::make('vat_number')
                            ->placeholder(__('admin.customers.customer.fields.vat_number'))
                            ->label(__('admin.customers.customer.fields.vat_number')),
                    ])
                    ->columnSpanFull()
                    ->label(__('admin.customers.customer.fields.company_data')),
                Fieldset::make(__('admin.customers.customer.fields.privacy_data'))
                    ->schema([
                        DateTimePicker::make('gdpr_consent_at')
                            ->dehydrated(false)
                            ->disabled()
                            ->label(__('admin.customers.customer.fields.gdpr_consent_at')),
                        DateTimePicker::make('anonymized_at')
                            ->dehydrated(false)
                            ->disabled()
                            ->label(__('admin.customers.customer.fields.anonymized_at')),
                    ])
                    ->columnSpanFull(),

            ])
            ->statePath('data');
    }

    /**
     * Build customer address form from store_settings => checkout_settings
     */
    private static function buildFixedFields(array $checkoutFields): array
    {
        $components = [];

        foreach ($checkoutFields as $field) {
            $type       = $field['type'] ?? null;
            $label      = self::translatedLabel($field);
            $required   = (bool) ($field['is_required'] ?? false);

            $component = match ($type) {
                'country' => Select::make('country_iso')
                    ->options(fn () => Country::query()
                        ->where('is_active', true)
                        ->get()
                        ->mapWithKeys(fn (Country $c) => [$c->iso_code => $c->name]))
                    ->searchable(),
                'city'          => TextInput::make('city')->maxLength(255),
                'street'        => TextInput::make('street')->maxLength(255),
                'building'      => TextInput::make('building')->maxLength(50),
                'apartment'     => TextInput::make('apartment')->maxLength(50),
                'postal_code'   => TextInput::make('postal_code')->maxLength(20),
                'phone'         => TextInput::make('phone')->tel()->maxLength(30),
                'company'       => TextInput::make('company')->maxLength(255),
                'vat_number'    => TextInput::make('vat_number')->maxLength(50),
                'region'        => TextInput::make('region')->maxLength(255),
                default         => null, // Unknown config field type. Skip silently, nothing breaks
            };

            // Set component labels and required marks
            if ($component !== null) {
                $components[] = $component->label($label)->required($required);
            }
        }

        return $components;
    }

    /**
     * Custom fields in user address
     */
    private static function buildCustomFields(array $customFields): array
    {
        $components = [];
        
        foreach ($customFields as $field) {
            $type       = $field['type'] ?? 'text';
            $label      = self::translatedLabel($field);
            $required   = (bool) ($field['is_required'] ?? false);

            
            $key = $field['name'] ?? null;

            if (blank($key)) {
                continue; // Safety check: if somehow custom field has no own name, then skip it
            }
            $name   = "custom_fields.{$key}";

            $components[] = match ($type) {
                'date'      => DatePicker::make($name),
                'datetime'  => DateTimePicker::make($name),
                'time'      => TimePicker::make($name),
                'textarea'  => Textarea::make($name),
                'checkbox'  => Checkbox::make($name),
                default     => TextInput::make($name)->maxLength(255),
            };

            $components[count($components) - 1]->label($label)->required($required);
        }

        return $components;
    }

    private static function translatedLabel(array $field): string
    {
        return $field['label'][app()->getLocale()]
            ?? $field['label']['en']
            ?? $field['type'] ?? '';
    }
}
