<?php

namespace App\Filament\Schemas\Tabs;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\FusedGroup;
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
                            ->helperText(__('admin.store_contacts.helpers.legal_name')),

                        Textarea::make("organization_description.{$locale}")
                            ->label(__('admin.store_contacts.fields.organization_description'))
                            ->placeholder(__('admin.store_contacts.fields.organization_description'))
                            ->helperText(__('admin.store_contacts.helpers.organization_description')),

                        Textarea::make("local_business_description.{$locale}")
                            ->label(__('admin.store_contacts.fields.local_business_description'))
                            ->placeholder(__('admin.store_contacts.fields.local_business_description'))
                            ->helperText(__('admin.store_contacts.helpers.local_business_description'))
                    ]),

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
                    ]),

                Fieldset::make(__('admin.store_contacts.fields.geo_infos'))
                    ->schema([
                        FusedGroup::make([
                            TextInput::make("latitude.{$locale}")
                                ->prefix($locale)
                                ->label(__('admin.store_contacts.fields.latitude'))
                                ->placeholder(__('admin.store_contacts.fields.latitude')),
    
                            TextInput::make("longitude.{$locale}")
                                ->prefix($locale)
                                ->label(__('admin.store_contacts.fields.longitude'))
                                ->placeholder(__('admin.store_contacts.fields.longitude')),
                        ])
                        ->label(__('admin.store_contacts.fields.latitude'). ' / ' . __('admin.store_contacts.fields.longitude'))
                        ->helperText(__('admin.store_contacts.helpers.latitude'))
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
                                TableColumn::make(__('admin.store_contacts.fields.phone_name'))->width('50%')->markAsRequired(),
                                TableColumn::make(__('admin.store_contacts.fields.phone_number'))->width('50%')->markAsRequired(),
                            ])
                            ->schema([
                                TextInput::make('name')->label(__('admin.store_contacts.fields.phone_name'))->required(),
                                TextInput::make('number')->label(__('admin.store_contacts.fields.phone_number'))->required(),
                            ])
                            ->addActionLabel(__('admin.store_contacts.buttons.add_phone'))
                            ->compact()
                            ->columnSpanFull(),
                    ]),

                // Open hours
                Fieldset::make(__('admin.store_contacts.fields.open_hours'))
                    ->schema([
                        Repeater::make("open_hours.{$locale}")
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make(__('admin.store_contacts.fields.day'))->markAsRequired(),
                                TableColumn::make(__('admin.store_contacts.fields.opens'))->markAsRequired(),
                                TableColumn::make(__('admin.store_contacts.fields.closes'))->markAsRequired(),
                            ])
                            ->schema([
                                TextInput::make('day')->label(__('admin.store_contacts.fields.day'))->required(),
                                TextInput::make('opens')->label(__('admin.store_contacts.fields.opens'))->required(),
                                TextInput::make('closes')->label(__('admin.store_contacts.fields.closes'))->required(),
                            ])
                            ->addActionLabel(__('admin.store_contacts.buttons.add_open_hours'))
                            ->reorderable(false)
                            ->compact()
                            ->helperText(new HtmlString(__('admin.store_contacts.helpers.open_hours'))),
                    ]),
                
                // Social links
                Fieldset::make(__('admin.store_contacts.fields.social_links'))
                    ->schema([
                        Repeater::make("social_links.{$locale}")
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make(__('admin.store_contacts.fields.social_link_icon'))->width('100px'),
                                TableColumn::make(__('admin.store_contacts.fields.social_link_title'))->markAsRequired(),
                                TableColumn::make(__('admin.store_contacts.fields.social_link_link'))->markAsRequired(),
                            ])
                            ->schema([
                                FileUpload::make('icon')->placeholder(__('admin.store_contacts.fields.social_link_icon'))->panelLayout('compact'),
                                TextInput::make('name')->label(__('admin.store_contacts.fields.social_link_title'))->required(),
                                TextInput::make('link')->label(__('admin.store_contacts.fields.social_link_link'))->required(),
                            ])
                            ->addActionLabel(__('admin.store_contacts.buttons.add_social_link'))
                            ->reorderable(false)
                            ->compact(),
                    ]),
                
                // Social contacts
                Fieldset::make(__('admin.store_contacts.fields.social_contacts'))
                    ->schema([
                        Repeater::make("social_contacts.{$locale}")
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make(__('admin.store_contacts.fields.social_contact_icon'))->width('100px'),
                                TableColumn::make(__('admin.store_contacts.fields.social_contact_title'))->markAsRequired(),
                                TableColumn::make(__('admin.store_contacts.fields.social_contact_link'))->markAsRequired(),
                            ])
                            ->schema([
                                FileUpload::make('icon')->placeholder(__('admin.store_contacts.fields.social_contact_icon'))->panelLayout('compact'),
                                TextInput::make('name')->label(__('admin.store_contacts.fields.social_contact_title'))->required(),
                                TextInput::make('link')->label(__('admin.store_contacts.fields.social_contact_link'))->required(),
                            ])
                            ->addActionLabel(__('admin.store_contacts.buttons.add_social_contact'))
                            ->reorderable(false)
                            ->compact(),
                        Callout::make(__('admin.store_contacts.fields.social_contacts'))
                            ->description(new HtmlString(__('admin.store_contacts.helpers.social_contacts')))
                            ->info()
                            ->columnSpanFull()
                    ]),
            ];
    }
}