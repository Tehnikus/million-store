<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Domain\Catalog\Actions\UpsertProduct;
use App\Models\Catalog\Option;
use App\Models\Catalog\OptionValue;
use Arr;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Context;


class OptionsTab
{
    public static function make($store, $languages): Tab
    {
        return Tab::make('productOptions')
            ->badge(fn (Get $get) => \count($get('combinations') ?? []) ?: null)
            ->schema([

                Section::make(__('admin.catalog.products.tabs.options.labels.options'))
                    ->schema([
                    Select::make('axes')
                        ->label(__('admin.catalog.products.tabs.options.labels.options'))
                        ->noOptionsMessage(__('admin.catalog.products.tabs.options.placeholders.no_options'))
                        ->searchPrompt(__('admin.catalog.products.tabs.options.placeholders.search_options'))
                        ->placeholder(__('admin.catalog.products.tabs.options.placeholders.search_options'))
                        ->searchingMessage(__('admin.catalog.products.tabs.options.placeholders.searching_options'))
                        ->multiple()
                        ->options(fn() => static::optionChoices($store->id))
                        ->preload()
                        ->live(debounce:0)
                        ->searchDebounce(200)
                        ->partiallyRenderComponentsAfterStateUpdated(['values', 'priceTiers', 'description.options_description'])
                        ->afterStateUpdated(function (Get $get, Set $set) use ($store) {
                            $validValueIds = array_keys(static::valuesForAxes((array) $get('axes'), $store->id));
                            $set('values', array_values(array_intersect((array) $get('values'), $validValueIds)));
                        })
                        ->columnSpan(1),
                    Select::make('values')
                        ->label(__('admin.catalog.products.tabs.options.labels.option_vals'))
                        ->multiple()
                        ->options(fn(Get $get) => static::valuesForAxes((array) $get('axes'), $store->id))
                        ->preload()
                        ->live(debounce:0)
                        ->searchDebounce(200)
                        ->partiallyRenderComponentsAfterStateUpdated(['combinations', 'priceTiers', 'description.options_description'])
                        ->afterStateUpdated(fn(Get $get, Set $set, ?Model $record) => static::refresh($get, $set, $record, $store->id))
                        ->columnSpan(2),

                    CheckboxList::make('combinations')
                        ->label(__('admin.catalog.products.tabs.options.labels.variants_selector'))
                        ->belowLabel(__('admin.catalog.products.tabs.options.helpers.variants_selector'))
                        ->noSearchResultsMessage(__('admin.catalog.products.tabs.options.placeholders.no_variants'))
                        ->searchPrompt(__('admin.catalog.products.tabs.options.placeholders.variants_search'))
                        ->searchDebounce(0)
                        ->options(fn(Get $get) => static::generateCombinations(
                            static::axisValuesFromFlat((array) $get('axes'), (array) $get('values'), $store->id)
                        ))
                        ->searchable()
                        ->bulkToggleable()
                        ->columns(4)
                        ->columnSpanFull()
                        ->live(debounce: 0)
                        ->partiallyRenderComponentsAfterStateUpdated(['description.options_description', 'priceTiers'])
                        ->afterStateUpdated(fn(Get $get, Set $set, ?Model $record) => static::syncDescriptions((array) $get('combinations'), $get, $set, $record, $store->id))
                        ->afterStateUpdatedJs(static::badgeUpdateJs('$state.length'))
                ])
                ->columns(3),


                Repeater::make('options_description')
                    ->schema([
                        FusedGroup::make(
                            collect($languages)->map(fn ($language) =>
                                TextInput::make("name.{$language->locale}")
                                    ->live(onBlur: true)
                                    ->prefix($language->locale)
                                    ->required(fn(Get $get) => $get('option_id') !== null)
                                    ->visible(fn(Get $get) => $get('option_id') !== null)
                                    ->hiddenLabel()
                                    ->label(__('admin.catalog.options.fields.group_name'))
                                    
                            )->all()
                        )
                        ->helperText(__('admin.catalog.options.helpers.group_name')),

                        Repeater::make('description')
                            ->schema([
                                Hidden::make('option_value_id'),
                                // Text::make('label')
                                //     ->content(fn (Get $get) => static::optionValueChoices($get('../../option_id'))->get($get('option_value_id')))
                                //     ->columnSpanFull(),
                                ...self::optionValueDescriptionsForm($languages),
                            ])
                            ->itemLabel(fn (array $state): ?string => static::itemLabelText($state))
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(true)
                            ->collapsible(true)
                            ->collapsed(fn($operation) => $operation !== 'create')
                            ->default([]),
                    ])
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(true)
                    ->collapsible(true)
                    ->collapsed(fn($operation) => $operation !== 'create')
                    ->default([])
                    ->statePath('description.options_description')
                    ->itemLabel(fn (array $state): ?string => static::groupItemLabel($state))
                    ->label(__('admin.catalog.products.tabs.options.labels.descriptions'))
                    ->belowLabel(__('admin.catalog.products.tabs.options.helpers.descriptions'))
            ]);
    }

    protected static function valuesForAxes(array $axisIds, int $storeId): array
    {
        $axisNames = static::optionChoices($storeId);

        return collect($axisIds)
            ->map(fn ($id) => (int) $id)
            ->mapWithKeys(fn ($axisId) => [
                $axisNames->get($axisId, "#{$axisId}") => static::optionValueChoices($axisId)->all(),
            ])
            ->all();
    }

    protected static function axisValuesFromFlat(array $axisIds, array $valueIds, int $storeId): array
    {
        $valueIds = array_map('intval', $valueIds);

        return collect($axisIds)
            ->map(fn ($id) => (int) $id)
            ->mapWithKeys(fn ($axisId) => [
                $axisId => array_values(array_intersect($valueIds, array_keys(static::optionValueChoices($axisId)->all()))),
            ])
            ->filter()
            ->all();
    }

    protected static function generateCombinations(array $axisValues): array
    {
        $axes = collect($axisValues)
            ->map(fn ($ids) => array_map('intval', (array) $ids))
            ->filter();

        if ($axes->isEmpty()) {
            return [];
        }

        return $axes
            ->reduce(fn (Collection $carry, array $valueIds, $axisId) =>
                $carry->flatMap(fn ($combo) =>
                    collect($valueIds)->map(fn ($id) => $combo + [(int) $axisId => $id])
                ), collect([[]]))
            ->mapWithKeys(fn ($signature) => [
                UpsertProduct::signatureKey($signature) => collect($signature)
                    ->map(fn ($valueId, $axisId) => static::optionValueChoices((int) $axisId)->get($valueId, "#{$valueId}"))
                    ->implode(' + '),
            ])
            ->all();
    }

    protected static function refresh(Get $get, Set $set, ?Model $record, int $storeId): void
    {
        $axisValues   = static::axisValuesFromFlat((array) $get('axes'), (array) $get('values'), $storeId);
        $valid        = array_keys(static::generateCombinations($axisValues));
        $combinations = array_values(array_intersect((array) $get('combinations'), $valid));
        $set('combinations', $combinations);

        static::syncDescriptions($combinations, $get, $set, $record, $storeId);
    }

    protected static function syncDescriptions(array $combinations, Get $get, Set $set, ?Model $record, int $storeId): void
    {
        $selected = collect($combinations)
            ->flatMap(fn ($key) => collect(UpsertProduct::signatureFromKey($key))
                ->map(fn ($valueId, $groupId) => ['option_id' => (int) $groupId, 'option_value_id' => (int) $valueId])
                ->values())
            ->unique(fn ($row) => "{$row['option_id']}-{$row['option_value_id']}")
            ->groupBy('option_id');

        $current   = collect($get('description.options_description'));
        $productId = $record?->id;

        $override = $productId
            ? $record->descriptions()
                ->where('product_id', $productId)
                ->where('store_id', $storeId)
                ->first()
                ?->options_description
            : null;

        $rebuilt = $selected->map(function ($rows, $optionId) use ($current, $override) {
            $existingOption = $current->first(fn ($o) => (int) ($o['option_id'] ?? null) === (int) $optionId);
            $existingValues = collect($existingOption['description'] ?? []);

            $values = $rows->map(function ($row) use ($existingValues, $override, $optionId) {
                return $existingValues->first(fn ($v) => (int) ($v['option_value_id'] ?? null) === $row['option_value_id'])
                    ?? static::defaultOptionValueData($row['option_value_id'], $optionId, $override);
            })->values()->all();

            return [
                'option_id'   => $optionId,
                'name'        => $existingOption['name'] ?? static::defaultOptionName($optionId, $override),
                'description' => $values,
            ];
        })->values()->all();
        
        $set('description.options_description', $rebuilt);
    }

    protected static function defaultOptionName(int $optionId, ?array $override): array
    {
        $default = Option::find($optionId);
        if (!$default)
            return [];

        $overrideGroup = collect($override)
            ->first(fn($group) => (int) ($group['option_id'] ?? null) === $optionId);

        $nameOverride = $overrideGroup['name'] ?? [];
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

        $overrideGroup = collect($override)
            ->first(fn($group) => (int) ($group['option_id'] ?? null) === $optionId);

        $valueOverride = collect($overrideGroup['description'] ?? [])
            ->first(fn($v) => (int) ($v['option_value_id'] ?? null) === $valueId) ?? [];

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
                                ->live(onBlur: true)
                                ->required()
                                ->maxLength(255)
                                ->prefix($language->locale)
                                ->label(__('admin.catalog.options.fields.option_name'))
                                ->placeholder(__('admin.catalog.options.fields.option_name'))
                                ->hiddenLabel(),

                            RichEditor::make("description.{$language->locale}")
                                ->columnSpanFull()
                                ->placeholder(__('admin.catalog.options.fields.description'))
                                ->toolbarButtons([
                                    'paragraph' => ['bold', 'italic', 'underline', 'link', 'textColor', 'alignStart', 'alignCenter', 'alignEnd', 'alignJustify', 'clearFormatting', 'undo', 'redo'],
                                ])
                                ->extraInputAttributes([
                                    'style' => 'min-height: 7rem; max-height: 18vh; overflow-y: auto;'
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

    private static function countProductOptions($record, $store): mixed
    {
        if (!$record) return null;
        $badge = $record->options()->where('store_id', $store->id)->count();

        return $badge !== 0 ? $badge : null;
    }

    // Single option item label for option descriptions repeater
    protected static function itemLabelText(array $state): ?string
    {
        $name = array_filter($state['name'] ?? []);
        if (blank($name)) return null;

        return $name[app()->getLocale()] ?? Arr::first($name);
    }

    // Item label for option descriptions repeater
    protected static function groupItemLabel(array $state): ?string
    {
        $groupName = static::itemLabelText($state);
        if (blank($groupName)) return null;

        // $locale = app()->getLocale();
        $valueNames = collect($state['description'] ?? [])
            ->map(fn ($v) => static::itemLabelText($v))
            ->filter()
            ->implode(', ');

        return $valueNames !== '' ? "{$groupName}: {$valueNames}" : $groupName;
    }

    // Render badge by JS
    protected static function badgeUpdateJs(string $countExpression, string $color = 'primary'): string
    {
        return <<<JS
            const tabRoot = \$el.closest('.fi-sc-tabs-tab');
            const dataKey = tabRoot.id.replace('form.', '');
            const button  = document.querySelector(`[data-tab-key="\${dataKey}"]`);
            const count   = {$countExpression};

            let badge = button?.querySelector('.fi-badge');

            if (count > 0) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'fi-color fi-color-{$color} fi-text-color-700 dark:fi-text-color-400 fi-badge fi-size-sm';
                    badge.innerHTML = '<span class="fi-badge-label-ctn"><span class="fi-badge-label"></span></span>';
                    button?.querySelector('.fi-tabs-item-label')?.after(badge);
                }
                badge.querySelector('.fi-badge-label').textContent = count;
            } else {
                badge?.remove();
            }
            JS;
    }
}