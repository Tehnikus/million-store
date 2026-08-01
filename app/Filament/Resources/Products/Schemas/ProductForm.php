<?php

namespace App\Filament\Resources\Products\Schemas;
// use App\Models\Seo\Slug;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
// use Filament\Schemas\Components\Component;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
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


use App\Models\Customer\CustomerGroup;



class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        $languages = Filament::getTenant()->languages()->wherePivot('is_active', true)->get();
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
                                ->hiddenLabel()
                                ->helperText(__('admin.catalog.products.helpers.global_name')),
                        )->all(),
                        TextInput::make('sku')
                            ->label(__('admin.catalog.products.fields.sku'))
                            ->placeholder(__('admin.catalog.products.fields.sku'))
                            ->helperText(__('admin.catalog.products.helpers.sku')),

                    ])
                    ->collapsible()
                    ->collapsed(fn($operation) => $operation !== 'create')
                    ->columnSpanFull(),

                Tabs::make('product')
                    ->schema([
                        // Store-scoped and translatable data 
                        Tab::make(__('admin.common.tabs.content'))
                            ->schema([
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
                            ]),

                        Tab::make(__('admin.catalog.products.tabs.prices'))
                            ->schema([
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
                                            // Nested repeater with all prices for this tier
                                            // See nested relation
                                            Repeater::make('prices')
                                                ->relationship('prices')
                                                ->table([
                                                    TableColumn::make(__('admin.catalog.products.fields.prices')),
                                                ])
                                                ->schema([
                                                    Group::make([
                                                        Hidden::make('currency_id'),
                                                        TextInput::make('price')
                                                            ->numeric()
                                                            ->step(0.01)
                                                            ->required()
                                                            ->prefix(fn(Get $get) => $currencies->firstWhere('id', $get('currency_id'))?->sign)
                                                            ->placeholder(fn(Get $get) => $currencies->firstWhere('id', $get('currency_id'))?->name)
                                                            ->live(),
                                                    ])
                                                ])
                                                // Display all available currencies
                                                ->default(
                                                    collect($currencies)->map(fn($currency) => [
                                                        'currency_id' => $currency->id,
                                                        'price' => null,
                                                    ])->all()
                                                )
                                                ->columnSpanFull()
                                                ->addable(false)
                                                ->deletable(false)
                                                ->compact()
                                                ->disableLabel()
                                                ->label(__('admin.catalog.products.fields.prices')),

                                            Group::make([
                                                Toggle::make('is_discount')
                                                    ->label(__('admin.catalog.products.fields.discount'))
                                                    ->helperText(__('admin.catalog.products.helpers.discount'))
                                                    ->inline(false)
                                                    ->live(),

                                                Select::make('customer_group_id')
                                                    ->relationship(
                                                        name: 'customerGroup',
                                                        titleAttribute: 'name',
                                                        modifyQueryUsing: fn(Builder $query) => $query->where('store_id', Filament::getTenant()->id),
                                                    )
                                                    ->searchable()
                                                    ->preload()
                                                    ->label(__('admin.catalog.products.fields.prices_customer_group'))
                                                    ->helperText(__('admin.catalog.products.helpers.prices_customer_group'))
                                                    ->live(),

                                                Group::make([
                                                    DateTimePicker::make('valid_from')
                                                        ->label(__('admin.catalog.products.fields.valid_from'))
                                                        ->helperText(__('admin.catalog.products.helpers.valid_from'))
                                                        ->live(),
    
                                                    DateTimePicker::make('valid_until')
                                                        ->label(__('admin.catalog.products.fields.valid_until'))
                                                        ->helperText(__('admin.catalog.products.helpers.valid_until'))
                                                        ->live(),
                                                ])
                                                ->columns(2),

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
                                                Group::make(
                                                    collect($languages)->map(
                                                        fn($language) =>
                                                        TextInput::make("name.{$language->locale}")
                                                            ->required()
                                                            ->prefix($language->locale)
                                                            ->columnSpanFull()
                                                            ->placeholder(__('admin.catalog.products.fields.price_name'))
                                                            ->label(__('admin.catalog.products.fields.price_name'))
                                                            ->helperText(__('admin.catalog.products.helpers.price_name'))
                                                            ->live(),
                                                    )->all(),
                                                ),
                                            ])
                                            ->columns(2)
                                            ->columnSpanFull(),
                                        ])
                                        ->orderColumn('priority')
                                        ->itemLabel(fn (array $state): ?string => 
                                            ($state['name'][app()->getLocale()] ?? null) . ' ' .

                                            
                                            // (!empty($state['prices']['price']) ? implode(', ', $state['prices']['price']) : null) . ' ' .
                                            ($state['customer_group_id'] ? CustomerGroup::find($state['customer_group_id'])?->name  : null) . ' '.
                                            ($state['is_discount']       ? __('admin.catalog.products.fields.discount') : null) . ' '.
                                            ($state['valid_from']        !== null ? __('admin.catalog.products.fields.valid_from_short') .' ' . $state['valid_from'] : '') . ' '.
                                            ($state['valid_until']       !== null ? __('admin.catalog.products.fields.valid_until_short') .' ' . $state['valid_until'] : '') . ' '
                                        )
                                        ->itemNumbers()
                                        ->defaultItems(1)
                                        ->columnSpanFull()
                                        ->collapsible()
                                        ->collapsed()
                                        ->label(__('admin.catalog.products.fields.price_tiers'))
                                        ->addActionLabel(__('admin.catalog.products.buttons.add_price_tier'))
                                        ->deleteAction(
                                            fn ($action) => $action->action(function (array $arguments, Repeater $component): void {
                                                $state = $component->getState();
                                                $keys = array_keys($state);
                                                
                                                // Check if the item being deleted is the first key
                                                if (!empty($keys) && $arguments['item'] === $keys[0]) {
                                                    Notification::make()
                                                        ->danger()
                                                        // ->title(__('admin.catalog.products.errors.last_price_title'))
                                                        ->body(__('admin.catalog.products.errors.last_price_text'))
                                                        ->send();
                                                    
                                                    return;
                                                }

                                                // Proceed with deletion for other items
                                                unset($state[$arguments['item']]);
                                                $component->state($state);
                                            })
                                        ),
                                ])
                                ->columnSpanFull(),
                            ])
                    ])->columnSpanFull(),
            ]);
    }
}