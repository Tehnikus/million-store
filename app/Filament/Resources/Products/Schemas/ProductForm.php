<?php

namespace App\Filament\Resources\Products\Schemas;
use App\Models\Store;
use App\Filament\Schemas\LanguageTabs;
use App\Filament\Schemas\Tabs\DescriptionTab;
use App\Filament\Schemas\Tabs\FaqTab;
use App\Filament\Schemas\Tabs\FooterTab;
use App\Filament\Schemas\Tabs\HowToTab;
use App\Filament\Schemas\Tabs\ImagesTab;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Group;

use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use App\Models\CustomerGroup;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        $languages = Filament::getTenant()->languages()->wherePivot('is_active', true)->get();
        $currencies = Filament::getTenant()->currencies()->wherePivot('is_active', true)->get();

        // $currencies = Store::with(['currencies' => function($query) {
        //     $query->wherePivot('is_active', true);
        // }])->get();
       
        return $schema
            ->statePath('data')
            ->schema([
                Tabs::make('product_store')->schema([

                    Tab::make('Status')->schema([
                        Section::make('Global data')
                            ->description('Shared across every store this product is in - editing it here changes it everywhere.')
                            ->schema([
                                ...collect($languages)->map(
                                    fn ($language) => TextInput::make("global_name.{$language->locale}")
                                        ->columnSpanFull()
                                        ->prefix($language->locale)
                                        ->placeholder('Internal name')
                                        ->hiddenLabel(),
                                )->all(),
                                TextInput::make('sku'),
                            ]),
                        Section::make('This store')->schema([
                            Toggle::make('is_active')
                                ->label('Page visible on frontend'),

                            Toggle::make('is_available')
                                ->label('Can be added to cart'),

                            DateTimePicker::make('is_available_from')
                                ->label('Available from (pre-order)'),

                            DateTimePicker::make('is_available_to')
                                ->label('Available until (limited offer)'),
                        ]),
                    ]),

                    Tab::make('description')->schema([
                        LanguageTabs::make($languages, [
                            [DescriptionTab::class, [
                                'withSlug' => true,
                                'sluggableType' => \App\Models\Catalog\ProductStore::class,
                            ]],
                            FaqTab::class,
                            HowToTab::class,
                            FooterTab::class,
                        ]),
                    ]),

                    Tab::make('Prices')
                        ->schema([
                            Repeater::make('prices')
                                // ->relationship()
                                ->orderColumn('sort_order')
                                ->table([
                                    TableColumn::make('price')->width('300px'),
                                    TableColumn::make('price_settings'),
                                ])
                                ->schema([
                                    Group::make(
                                        collect($currencies)->map(
                                            fn ($currency) => TextInput::make("price.{$currency->id}")
                                                ->columnSpanFull()
                                                ->prefix($currency->sign)
                                                ->placeholder('Price')
                                                ->hiddenLabel(),
                                        )->all(),
                                    ),
                                    Group::make([
                                        Toggle::make('is_discount')->label('Discount'),
                                        DateTimePicker::make('valid_from')->label('valid from'),
                                        DateTimePicker::make('valid_until')->label('valid to'),
                                        TextInput::make('valid_quantity')->numeric()->label('min valid quantity'),

                                        // Customer group with autocomplete
                                        // Select::make('customer_group_id')
                                        //     ->required()
                                        //     ->relationship(
                                        //         name: 'customerGroup',
                                        //         titleAttribute: 'name',
                                        //         modifyQueryUsing: fn(Builder $query) => $query->where('store_id', Filament::getTenant()->id),
                                        //     )
                                        //     ->getOptionLabelFromRecordUsing(fn(CustomerGroup $record) => $record->name)
                                        //     ->searchable()
                                        //     ->preload()
                                        //     ->label(__('admin.customers.customer.fields.customer_group_id'))
                                    ])
                                ]),

                        ]),

                    Tab::make('Images')
                        ->label(ImagesTab::label())
                        ->schema(ImagesTab::schema(['type' => 'product'])),
                ])->columnSpanFull(),
            ]);
    }
}
