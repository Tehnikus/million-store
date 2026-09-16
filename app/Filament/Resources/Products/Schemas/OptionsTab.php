<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Catalog\Option;
use App\Models\Catalog\OptionValue;
use App\Models\Catalog\ProductDescription;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Context;


class OptionsTab
{
    public static function make($store, $languages): Tab
    {
        return Tab::make('productOptions')
            ->label(__('admin.catalog.products.tabs.options'))
            ->schema([
                Repeater::make('optionSignatures')
                ->schema([
                    Repeater::make('selectedOptions')
                        ->label(__('admin.catalog.options.fields.combinations'))
                        ->table([
                            TableColumn::make(__('admin.catalog.options.fields.combinations'))
                        ])
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set, $livewire) use ($store) {
                            static::syncDescriptionRepeaters($get, $set, $livewire, $store->id);
                        })
                        ->schema([
                            FusedGroup::make([
                                Select::make('option_select')
                                    ->options(fn () => static::optionChoices($store->id))
                                    ->afterStateUpdated(fn (Set $set) => $set('option_value_select', null))
                                    ->live()
                                    ->preload()
                                    ->native(false)
                                    ->columnSpan(1),
                                Select::make('option_value_select')
                                    ->options(fn (Get $get) => static::optionValueChoices($get('option_select')))
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->live()
                                    ->preload()
                                    ->native(false)
                                    ->columnSpan(1),
                            ])->columns(2)->columnSpanFull(),
                        ])
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),

            Repeater::make('options')
                ->schema([
                    FusedGroup::make(
                        collect($languages)->map(fn ($language) =>
                            TextInput::make("name.{$language->locale}")
                                ->prefix($language->locale)
                                ->required(fn(Get $get) => $get('option_id') !== null)
                                ->visible(fn(Get $get) => $get('option_id') !== null)
                                ->hiddenLabel()
                                ->label(__('admin.catalog.options.fields.group_name'))
                                
                        )->all()
                    )
                    ->columnSpanFull()
                    ->helperText(__('admin.catalog.options.helpers.group_name')),

                    Repeater::make('productOptionValues')
                        ->schema([
                            Hidden::make('option_value_id'),
                            Text::make('label')
                                ->content(fn (Get $get) => static::optionValueChoices($get('../../option_id'))->get($get('option_value_id')))
                                ->columnSpanFull(),
                            ...self::optionValueDescriptionsForm($languages),
                        ])
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->columnSpanFull(),
                ])
                ->addable(false)
                ->deletable(false)
                ->reorderable(false)
                ->columnSpanFull(),
            ]);
    }

    protected static function syncDescriptionRepeaters(Get $get, Set $set, $livewire, int $storeId): void
    {
        $selected = collect($get('../../optionSignatures'))
            ->flatMap(fn ($signature) => collect($signature['selectedOptions'] ?? []))
            ->filter(fn ($row) => filled($row['option_select'] ?? null) && filled($row['option_value_select'] ?? null))
            ->map(fn ($row) => [
                'option_id'       => (int) $row['option_select'],
                'option_value_id' => (int) $row['option_value_select'],
            ])
            ->unique(fn ($row) => "{$row['option_id']}-{$row['option_value_id']}")
            ->groupBy('option_id');

        $current   = collect($get('options'));
        $productId = $livewire->getRecord()?->id;

        $override = $productId
            ? ProductDescription::where('product_id', $productId)
                ->where('store_id', $storeId)
                ->value('options_description')
            : null;

        $rebuilt = $selected->map(function ($rows, $optionId) use ($current, $override) {
            $existingOption = $current->first(fn ($o) => (int) ($o['option_id'] ?? null) === (int) $optionId);
            $existingValues = collect($existingOption['productOptionValues'] ?? []);

            $values = $rows->map(function ($row) use ($existingValues, $override, $optionId) {
                return $existingValues->first(fn ($v) => (int) ($v['option_value_id'] ?? null) === $row['option_value_id'])
                    ?? static::defaultOptionValueData($row['option_value_id'], $optionId, $override);
            })->values()->all();

            return [
                'option_id'           => $optionId,
                'name'                => $existingOption['name'] ?? static::defaultOptionName($optionId, $override),
                'productOptionValues' => $values,
            ];
        })->values()->all();

        $set('../../options', $rebuilt);
    }

    protected static function defaultOptionName(int $optionId, ?array $override): array
    {
        $default = Option::find($optionId);
        if (!$default) return [];

        $nameOverride = $override[$optionId]['name'] ?? [];
        $result = [];

        foreach ($default->getTranslations('name') as $locale => $name) {
            $result[$locale] = $nameOverride[$locale] ?? $name;
        }

        return $result;
    }

    protected static function defaultOptionValueData(int $valueId, int $optionId, ?array $override): array
    {
        $default = OptionValue::find($valueId)?->toArray();
        if (!$default) {
            return ['option_value_id' => $valueId, 'name' => [], 'description' => []];
        }

        $valueOverride = $override[$optionId]['values'][$valueId] ?? [];
        $name = $description = [];

        foreach ((array) $default['name'] as $locale => $n) {
            $name[$locale] = $valueOverride['name'][$locale] ?? $n;
        }
        foreach ((array) ($default['description'] ?? []) as $locale => $d) {
            $description[$locale] = $valueOverride['description'][$locale] ?? $d;
        }

        return ['option_value_id' => $valueId, 'name' => $name, 'description' => $description];
    }

    protected static function optionValueDescriptionsForm($languages)
    {
        return [
            Group::make(
                collect($languages)->map(
                    fn($language) =>
                    Fieldset::make($language->name)
                        ->schema([
                            TextInput::make("name.{$language->locale}")
                                ->required()
                                ->maxLength(255)
                                ->prefix($language->locale)
                                ->label(__('admin.catalog.options.fields.option_name'))
                                ->placeholder(__('admin.catalog.options.fields.option_name'))
                                ->hiddenLabel()
                                ->columnSpanFull(),

                            RichEditor::make("description.{$language->locale}")
                                ->columnSpanFull()
                                ->placeholder(__('admin.catalog.options.fields.description'))
                                ->toolbarButtons([
                                    'paragraph' => ['bold', 'italic', 'underline', 'link', 'textColor', 'alignStart', 'alignCenter', 'alignEnd', 'alignJustify', 'clearFormatting', 'undo', 'redo'],
                                ])
                                ->extraInputAttributes([
                                    'style' => 'min-height: 7rem; max-height: 15vh; overflow-y: auto;'
                                ])
                                ->hiddenLabel(),
                        ])
                        ->dense()
                )->all()
            )
        ];
    }

    protected static function optionValueChoices(?int $optionId): Collection
    {
        if (blank($optionId)) {
            return collect();
        }

        $key = "option_value_choices.{$optionId}";

        if (Context::has($key)) {
            return collect(Context::get($key));
        }

        $choices = OptionValue::query()
            ->where('option_id', $optionId)
            ->where('is_active', true)
            ->pluck('name', 'id');

        Context::add($key, $choices->all());

        return $choices;
    }

    protected static function optionChoices(int $storeId): Collection
    {
        $key = "option_choices.{$storeId}";

        if (Context::has($key)) {
            return collect(Context::get($key));
        }

        $choices = Option::query()
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->pluck('name', 'id');

        Context::add($key, $choices->all());

        return $choices;
    }
}