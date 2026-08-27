<?php

namespace App\Filament\Schemas\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Support\HtmlString;

class StoreContactForm
{
    public static function make($language): Tab
    {
        return Tab::make("contacts.{$language->locale}")
            ->schema(self::schema($language->locale))
            ->label($language->name);
    }

    private static function schema(string $locale): array
    {
        return [
                Fieldset::make(__('admin.store_contacts.fields.legal_infos'))
                    ->schema([
                        TextInput::make("legal_name.{$locale}")
                            ->prefix($locale)
                            ->label(__('admin.store_contacts.fields.legal_name'))
                            ->helperText(__('admin.store_contacts.helpers.legal_name'))
                            ->columnSpanFull(),

                        Textarea::make("organization_description.{$locale}")
                            ->label(__('admin.store_contacts.fields.organization_description'))
                            ->helperText(__('admin.store_contacts.helpers.organization_description'))
                            ->columnSpanFull(),

                        Textarea::make("local_business_description.{$locale}")
                            ->label(__('admin.store_contacts.fields.local_business_description'))
                            ->helperText(__('admin.store_contacts.helpers.local_business_description'))
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Fieldset::make(__('admin.store_contacts.fields.address_details'))
                    ->schema([
                        TextInput::make("address_country.{$locale}")
                            ->prefix($locale)
                            ->label(__('admin.store_contacts.fields.country'))
                            ->helperText(__('admin.store_contacts.helpers.country')),

                        TextInput::make("address_region.{$locale}")
                            ->prefix($locale)
                            ->label(__('admin.store_contacts.fields.region'))
                            ->helperText(__('admin.store_contacts.helpers.region')),

                        TextInput::make("address_locality.{$locale}")
                            ->prefix($locale)
                            ->label(__('admin.store_contacts.fields.city'))
                            ->helperText(__('admin.store_contacts.helpers.city')),

                        TextInput::make("address_street.{$locale}")
                            ->prefix($locale)
                            ->label(__('admin.store_contacts.fields.street'))
                            ->helperText(__('admin.store_contacts.helpers.street')),

                        TextInput::make("country_iso.{$locale}")
                            ->prefix($locale)
                            ->label(__('admin.store_contacts.fields.iso_code'))
                            ->helperText(__('admin.store_contacts.helpers.iso_code'))
                            ->maxLength(2),

                        TextInput::make("postal_code.{$locale}")
                            ->prefix($locale)
                            ->label(__('admin.store_contacts.fields.postal_code'))
                            ->helperText(__('admin.store_contacts.helpers.postal_code')),
                    ])
                    ->columnSpanFull(),
                Fieldset::make(__('admin.store_contacts.fields.geo_infos'))
                    ->schema([
                        TextInput::make("latitude.{$locale}")
                            ->prefix($locale)
                            ->label(__('admin.store_contacts.fields.latitude'))
                            ->helperText(__('admin.store_contacts.helpers.latitude')),

                        TextInput::make("longitude.{$locale}")
                            ->prefix($locale)
                            ->label(__('admin.store_contacts.fields.longitude'))
                            ->helperText(__('admin.store_contacts.helpers.longitude')),
                    ])
                    ->columnSpanFull(),
                TextInput::make("email.{$locale}")
                    ->prefix($locale)
                    ->label(__('admin.store_contacts.fields.email'))
                    ->email(),

                // Phones
                Fieldset::make(__('admin.store_contacts.fields.phones'))
                    ->schema([
                        Repeater::make("phones.{$locale}")
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make(__('admin.store_contacts.fields.phone_name'))->width('50%'),
                                TableColumn::make(__('admin.store_contacts.fields.phone_number'))->width('50%'),
                            ])
                            ->schema([
                                TextInput::make('name')->placeholder(__('admin.store_contacts.fields.phone_name')),
                                TextInput::make('number')->placeholder(__('admin.store_contacts.fields.phone_number')),
                            ])
                            ->addActionLabel(__('admin.store_contacts.buttons.add_phone'))
                            ->compact()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                // Open hours
                Fieldset::make(__('admin.store_contacts.fields.open_hours'))
                    ->schema([
                        Repeater::make("open_hours.{$locale}")
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make(__('admin.store_contacts.fields.day')),
                                TableColumn::make(__('admin.store_contacts.fields.opens')),
                                TableColumn::make(__('admin.store_contacts.fields.closes')),
                            ])
                            ->schema([
                                TextInput::make('day')->placeholder(__('admin.store_contacts.fields.day')),
                                TextInput::make('opens')->placeholder(__('admin.store_contacts.fields.opens')),
                                TextInput::make('closes')->placeholder(__('admin.store_contacts.fields.closes')),
                            ])
                            ->addActionLabel(__('admin.store_contacts.buttons.add_open_hours'))
                            ->reorderable(false)
                            ->compact()
                            ->columnSpanFull()
                            ->helperText(new HtmlString(__('admin.store_contacts.helpers.open_hours'))),
                    ])
                    ->columnSpanFull(),
                
                // Social links
                Fieldset::make(__('admin.store_contacts.fields.social_links'))
                    ->schema([
                        Repeater::make("social_links.{$locale}")
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make(__('admin.store_contacts.fields.social_link_icon'))->width('100px'),
                                TableColumn::make(__('admin.store_contacts.fields.social_link_title')),
                                TableColumn::make(__('admin.store_contacts.fields.social_link_link')),
                            ])
                            ->schema([
                                FileUpload::make('icon')->placeholder(__('admin.store_contacts.fields.social_link_icon'))->panelLayout('compact'),
                                TextInput::make('name')->placeholder(__('admin.store_contacts.fields.social_link_title')),
                                TextInput::make('link')->placeholder(__('admin.store_contacts.fields.social_link_link')),
                            ])
                            ->addActionLabel(__('admin.store_contacts.buttons.add_social_link'))
                            ->reorderable(false)
                            ->compact()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                
                // Social contacts
                Fieldset::make(__('admin.store_contacts.fields.social_contacts'))
                    ->schema([
                        Repeater::make("social_contacts.{$locale}")
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make(__('admin.store_contacts.fields.social_contact_icon'))->width('100px'),
                                TableColumn::make(__('admin.store_contacts.fields.social_contact_title')),
                                TableColumn::make(__('admin.store_contacts.fields.social_contact_link')),
                            ])
                            ->schema([
                                FileUpload::make('icon')->placeholder(__('admin.store_contacts.fields.social_contact_icon'))->panelLayout('compact'),
                                TextInput::make('name')->placeholder(__('admin.store_contacts.fields.social_contact_title')),
                                TextInput::make('link')->placeholder(__('admin.store_contacts.fields.social_contact_link')),
                            ])
                            ->addActionLabel(__('admin.store_contacts.buttons.add_social_contact'))
                            ->reorderable(false)
                            ->compact()
                            ->columnSpanFull(),
                        Callout::make(__('admin.store_contacts.fields.social_contacts'))
                            ->description(new HtmlString(__('admin.store_contacts.helpers.social_contacts')))
                            ->info()
                            ->columnSpanFull()
                    ])
                    ->columnSpanFull(),
            ];
    }
}