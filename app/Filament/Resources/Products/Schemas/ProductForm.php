<?php

namespace App\Filament\Resources\Products\Schemas;
// use App\Models\Seo\Slug;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
// use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\Model;

// use Illuminate\Support\Str;
// use Illuminate\Validation\Rule;

use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

// use Filament\Schemas\Components\Tabs;
// use Filament\Schemas\Components\Tabs\Tab;

// Reusable description tabs
use App\Filament\Schemas\LanguageTabs;
use App\Filament\Schemas\Tabs\DescriptionTab;
use App\Filament\Schemas\Tabs\FaqTab;
use App\Filament\Schemas\Tabs\FooterTab;
use App\Filament\Schemas\Tabs\HowToTab;
// use App\Filament\Schemas\Tabs\ImagesTab;


use App\Models\CustomerGroup;



class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        $languages  = Filament::getTenant()->languages()->wherePivot('is_active', true)->get();
        $currencies = Filament::getTenant()->currencies()->wherePivot('is_active', true)->get();

        return $schema
            ->components([
                // Global data
                Section::make(__('admin.catalog.products.fields.global_data'))
                    ->description(__('admin.catalog.products.helpers.global_data'))
                    ->schema([
                        ...collect($languages)->map(
                            fn($language) => 
                                TextInput::make("global_name.{$language->locale}")
                                    ->required()
                                    ->prefix($language->locale)
                                    ->columnSpanFull()
                                    ->placeholder(__('admin.catalog.products.fields.global_name'))
                                    ->hiddenLabel(),
                        )->all(),
                        TextInput::make('sku')
                            ->label(__('admin.catalog.products.fields.sku'))
                            ->placeholder(__('admin.catalog.products.fields.sku')),
                        
                    ])
                    ->columnSpanFull(),

                // Store-scoped data
                Group::make([
                    Toggle::make('is_active')
                        ->label(__('admin.catalog.products.fields.is_active'))
                        ->helperText(__('admin.catalog.products.helpers.is_active')),
                    LanguageTabs::make($languages, [
                        [DescriptionTab::class, ['withSlug' => true]],
                        FaqTab::class,
                        HowToTab::class,
                        FooterTab::class,
                    ])
                ])
                // ->relationship(name:'storeDescription')
                ->statePath('description')
                ->columnSpanFull(),

                Group::make([
                    Repeater::make('product_price_tiers')
                        ->relationship(
                            name: 'priceTiers',
                            modifyQueryUsing: fn(Builder $query) => $query->where('store_id', Filament::getTenant()->id),
                        )
                        ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                            $data['store_id'] = Filament::getTenant()->id;
                            return $data;
                        })
                        ->schema([
                            // Вложенный репитер — цены по валютам внутри одного тира
                            Repeater::make('prices')
                                ->relationship('prices')
                                ->schema([
                                    Select::make('currency_id')
                                        ->options($currencies->pluck('sign', 'id'))
                                        ->required()
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems(),

                                    TextInput::make('price')
                                        ->numeric()
                                        ->required()
                                        ->prefix(fn(Get $get) => $currencies->firstWhere('id', $get('currency_id'))?->sign),
                                ])
                                ->columns(2)
                                // Стартуем с одной строки на каждую активную валюту магазина —
                                // применяется только при создании НОВОГО тира; при
                                // редактировании существующего Filament подтянет реальные
                                // ProductPrice через relationship вместо этого дефолта
                                ->default(
                                    collect($currencies)->map(fn($currency) => [
                                        'currency_id' => $currency->id,
                                        'price' => null,
                                    ])->all()
                                )
                                ->columnSpanFull()
                                ->label(__('admin.catalog.products.fields.prices')),

                            Group::make([
                                Toggle::make('is_discount')
                                    ->label(__('admin.catalog.products.fields.discount'))
                                    ->helperText(__('admin.catalog.products.helpers.discount')),

                                Select::make('customer_group_id')
                                    ->relationship(
                                        name: 'customerGroup',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn(Builder $query) => $query->where('store_id', Filament::getTenant()->id),
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->label(__('admin.catalog.products.fields.prices_customer_group'))
                                    ->helperText(__('admin.catalog.products.helpers.prices_customer_group')),

                                DateTimePicker::make('valid_from')
                                    ->label(__('admin.catalog.products.fields.valid_from'))
                                    ->helperText(__('admin.catalog.products.helpers.valid_from')),

                                DateTimePicker::make('valid_until')
                                    ->label(__('admin.catalog.products.fields.valid_until'))
                                    ->helperText(__('admin.catalog.products.helpers.valid_until')),

                                TextInput::make('valid_quantity')
                                    ->numeric()
                                    ->label(__('admin.catalog.products.fields.valid_quantity'))
                                    ->helperText(__('admin.catalog.products.helpers.valid_quantity')),

                                TextInput::make('priority')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->label(__('admin.catalog.products.fields.priority'))
                                    ->helperText(__('admin.catalog.products.helpers.priority')),
                            ])
                                ->columns(2)
                                ->columnSpanFull(),
                        ])
                        ->defaultItems(1)
                        ->addActionLabel(__('admin.catalog.products.buttons.add_price_tier'))
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
            ]);
    }
}