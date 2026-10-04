<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Domain\Catalog\Actions\UpsertProduct;
use App\Models\Catalog\{Option, OptionValue};
use Arr;
use Filament\Forms\Components\{CheckboxList, Hidden, Repeater, RichEditor, Select, TextInput};
use Filament\Schemas\Components\{Fieldset, FusedGroup, Group, Section, Tabs\Tab, Utilities\Get, Utilities\Set};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class OptionsTab
{
    public static function make($store, $languages): Tab
    {
        return Tab::make('productOptions')
            ->badge(fn (Get $get) => \count($get('combinations') ?? []) ?: null)
            ->schema([

                Section::make(__('admin.catalog.products.tabs.options.labels.options'))
                    ->description(__('admin.catalog.products.tabs.options.helpers.options'))
                    ->schema([
                        Select::make('optionGroups')
                            ->label(__('admin.catalog.products.tabs.options.labels.options'))
                            ->noOptionsMessage(__('admin.catalog.products.tabs.options.placeholders.no_options'))
                            ->searchPrompt(__('admin.catalog.products.tabs.options.placeholders.search_options'))
                            ->placeholder(__('admin.catalog.products.tabs.options.placeholders.search_options'))
                            ->searchingMessage(__('admin.catalog.products.tabs.options.placeholders.searching_options'))
                            ->multiple()
                            ->options(fn() => Option::optionChoices($store->id))
                            ->preload()
                            ->live(debounce:0)
                            ->searchDebounce(200)
                            ->reorderable()
                            ->partiallyRenderComponentsAfterStateUpdated(['combinations', 'optionGroupValues', 'priceTiers', 'description.options_description'])
                            ->afterStateUpdated(function (Get $get, Set $set, ?Model $record) use ($store) {
                                $validValueIds = static::validOptionValueIds((array) $get('optionGroups'), $store->id);
                                $set('optionGroupValues', array_values(array_intersect((array) $get('optionGroupValues'), $validValueIds)));
                                static::refreshCombinationCheckboxes($get, $set, $record, $store->id);
                            })
                            ->columnSpan(1),
                        Select::make('optionGroupValues')
                            ->label(__('admin.catalog.products.tabs.options.labels.option_vals'))
                            ->noOptionsMessage(__('admin.catalog.products.tabs.options.placeholders.no_option_vals'))
                            ->searchPrompt(__('admin.catalog.products.tabs.options.placeholders.search_option_vals'))
                            ->placeholder(__('admin.catalog.products.tabs.options.placeholders.search_option_vals'))
                            ->searchingMessage(__('admin.catalog.products.tabs.options.placeholders.searching_option_vals'))
                            ->multiple()
                            ->options(fn(Get $get) => OptionValue::optionValueGroupedChoices((array) $get('optionGroups'), $store->id))
                            ->preload()
                            ->live(debounce:0)
                            ->searchDebounce(200)
                            ->partiallyRenderComponentsAfterStateUpdated(['combinations', 'priceTiers', 'description.options_description'])
                            ->afterStateUpdated(fn(Get $get, Set $set, ?Model $record) => static::refreshCombinationCheckboxes($get, $set, $record, $store->id))
                            ->columnSpan(2),

                        CheckboxList::make('combinations')
                            ->label(__('admin.catalog.products.tabs.options.labels.variants_selector'))
                            ->helperText(__('admin.catalog.products.tabs.options.helpers.variants_selector'))
                            ->noSearchResultsMessage(__('admin.catalog.products.tabs.options.placeholders.no_variants'))
                            ->searchPrompt(__('admin.catalog.products.tabs.options.placeholders.variants_search'))
                            ->searchDebounce(0)
                            ->options(fn(Get $get) => static::generateCombinations(
                                static::optionValuesFromFlat((array) $get('optionGroups'), (array) $get('optionGroupValues'), $store->id),
                                $store->id
                            ))
                            ->visible(fn (Get $get) => filled($get('optionGroupValues')))
                            ->searchable()
                            ->bulkToggleable()
                            ->columns(fn(Get $get) => Option::find(Arr::first($get('optionGroups'), null, 1))->values()->count())
                            ->columnSpan(3)
                            ->live(debounce: 0)
                            ->partiallyRenderComponentsAfterStateUpdated(['description.options_description', 'priceTiers'])
                            ->afterStateUpdated(fn(Get $get, Set $set, ?Model $record) => static::syncDescriptions((array) $get('combinations'), $get, $set, $record, $store->id))
                            ->afterStateUpdatedJs(static::badgeUpdateJs('$state.length'))
                    ])
                    ->columns(3)
                    ->collapsible(),

                Repeater::make('options_description')
                    ->schema([
                        Hidden::make('option_id'),
                        FusedGroup::make(
                            collect($languages)->map(fn ($language) =>
                                TextInput::make("name.{$language->locale}")
                                    ->live(onBlur: true)
                                    ->prefix($language->locale)
                                    ->required(fn(Get $get) => $get('option_id') !== null)
                                    ->visible(fn(Get $get) => $get('option_id') !== null)
                                    ->hiddenLabel()
                                    ->label(__('admin.catalog.products.tabs.options.labels.group_name'))
                                    
                            )->all()
                        )
                        ->helperText(__('admin.catalog.products.tabs.options.helpers.group_name')),

                        Repeater::make('description')
                            ->schema([
                                Hidden::make('option_value_id'),
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
                    ->helperText(__('admin.catalog.products.tabs.options.helpers.descriptions'))
                    ->visible(fn(Get $get) => filled($get('combinations')))
            ]);
    }

    /**
     * The form of option descriptions in Repeater::make('options_description')
     * @param mixed $languages
     * @return Group[]
     */
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
                                ->label(__('admin.catalog.products.tabs.options.labels.option_val_name'))
                                ->placeholder(__('admin.catalog.products.tabs.options.labels.option_val_name'))
                                ->hiddenLabel(),

                            RichEditor::make("description.{$language->locale}")
                                ->columnSpanFull()
                                ->placeholder(__('admin.catalog.products.tabs.options.helpers.description'))
                                ->helperText(__('admin.catalog.products.tabs.options.helpers.description'))
                                ->extraInputAttributes([
                                    'style' => 'min-height: 7rem; max-height: 14vh; overflow-y: auto;'
                                ])
                                ->hiddenLabel(),
                        ])
                        ->dense()
                )->all()
            )
        ];
    }

    // Item label for Repeater::make('options_description') to reflect changes in repeater's fields
    protected static function groupItemLabel(array $state): ?string
    {
        $groupName = static::itemLabelText($state);
        if (blank($groupName)) return null;

        $valueNames = collect($state['description'] ?? [])
            ->map(fn ($v) => static::itemLabelText($v))
            ->filter()
            ->implode(', ');

        return $valueNames !== '' ? "{$groupName}: {$valueNames}" : $groupName;
    }

    // Single option item label for for Repeater::make('options_description') with respect to current locale and fallback
    protected static function itemLabelText(array $state): ?string
    {
        $name = array_filter($state['name'] ?? []);
        if (blank($name)) return null;

        return $name[app()->getLocale()] ?? Arr::first($name);
    }

    protected static function validOptionValueIds(array $axisIds, int $storeId): array
    {
        return collect($axisIds)
            ->map(fn ($id) => (int) $id)
            ->flatMap(fn ($axisId) => array_keys(OptionValue::optionValueChoices($axisId, $storeId)->all()))
            ->all();
    }

    /**
     * Multiply arrays of option values to generate combinations of every option value to every other option value
     * @param array $axisIds
     * @param array $valueIds
     * @param int $storeId
     * @return array[]
     */
    protected static function optionValuesFromFlat(array $axisIds, array $valueIds, int $storeId): array
    {
        $valueIds = array_map('intval', $valueIds);

        return collect($axisIds)
            ->map(fn ($id) => (int) $id)
            ->mapWithKeys(fn ($axisId) => [
                $axisId => array_values(array_intersect($valueIds, array_keys(OptionValue::optionValueChoices($axisId, $storeId)->all()))),
            ])
            ->filter()
            ->all();
    }

    /**
     * Generate valid combinations of options for each checkbox in CheckboxList
     * @param array $axisValues
     * @return string[]
     */
    protected static function generateCombinations(array $axisValues, int $storeId): array
    {
        $optionGroups = collect($axisValues)
            ->map(fn ($ids) => array_map('intval', (array) $ids))
            ->filter();

        if ($optionGroups->isEmpty()) {
            return [];
        }

        return $optionGroups
            ->reduce(fn (Collection $carry, array $valueIds, $axisId) =>
                $carry->flatMap(fn ($combo) =>
                    collect($valueIds)->map(fn ($id) => $combo + [(int) $axisId => $id])
                ), collect([[]]))
            ->mapWithKeys(fn ($signature) => [
                UpsertProduct::signatureKey($signature) => collect($signature)
                    ->map(fn ($valueId, $axisId) => OptionValue::optionValueChoices((int) $axisId, $storeId)->get($valueId, "#{$valueId}"))
                    ->implode(' + '),
            ])
            ->all();
    }

    /**
     * Refresh combination checkboxes
     * Set the the state of CheckboxList with generated options list
     * @param Get $get
     * @param Set $set
     * @param mixed $record
     * @param int $storeId
     * @return void
     */
    protected static function refreshCombinationCheckboxes(Get $get, Set $set, ?Model $record, int $storeId): void
    {
        $optionGroups = static::optionValuesFromFlat((array) $get('optionGroups'), (array) $get('optionGroupValues'), $storeId);
        $valid        = array_keys(static::generateCombinations($optionGroups, $storeId));
        $saved        = static::savedCombinationKeys($record, $storeId);

        $combinations = array_values(array_unique(array_merge(
            array_intersect((array) $get('combinations'), $valid),
            array_intersect($saved, $valid),
        )));

        $set('combinations', $combinations);
        static::syncDescriptions($combinations, $get, $set, $record, $storeId);
    }

    protected static function savedCombinationKeys(?Model $record, int $storeId): array
    {
        if (!$record) {
            return [];
        }

        return $record->options()
            ->where('store_id', $storeId)
            ->get()
            ->map(fn ($option) => UpsertProduct::signatureKey($option->option_signature))
            ->values()
            ->all();
    }

    /**
     * Sync options descriptions with ProductDescription if available,
     * or Option and OptionValue if not
     * These values are stored as an override for options names and captions on product page
     * Triggered by changing checkboxes of options linked to the product
     * @param array $combinations
     * @param Get $get
     * @param Set $set
     * @param mixed $record
     * @param int $storeId
     * @return void
     */
    protected static function syncDescriptions(array $combinations, Get $get, Set $set, ?Model $record, int $storeId): void
    {
        $selected = collect($combinations)
            ->flatMap(fn ($key) => collect(UpsertProduct::signatureFromKey($key))
                ->map(fn ($valueId, $groupId) => ['option_id' => (int) $groupId, 'option_value_id' => (int) $valueId])
                ->values())
            ->unique(fn ($row) => "{$row['option_id']}-{$row['option_value_id']}")
            ->groupBy('option_id');

        if ($selected->isEmpty()) {
            $set('description.options_description', []);
            return;
        }

        // Get all data batches in two queries instead of N+1 query for every value
        $optionModels = Option::whereIn('id', $selected->keys()->all())->get()->keyBy('id');
        $valueModels  = OptionValue::whereIn(
            'id',
            $selected->flatMap(fn ($rows) => $rows)->pluck('option_value_id')->all()
        )->get()->keyBy('id');

        $current   = collect($get('description.options_description'));
        $productId = $record?->id;

        $override = $productId
            ? $record->descriptions()
                ->where('product_id', $productId)
                ->where('store_id', $storeId)
                ->first()
                ?->options_description
            : null;

        $rebuilt = $selected->map(function ($rows, $optionId) use ($current, $override, $optionModels, $valueModels) {
            $existingOption = $current->first(fn ($o) => (int) ($o['option_id'] ?? null) === (int) $optionId);
            $existingValues = collect($existingOption['description'] ?? []);

            $values = $rows->map(function ($row) use ($existingValues, $override, $optionId, $valueModels) {
                return $existingValues->first(fn ($v) => (int) ($v['option_value_id'] ?? null) === $row['option_value_id'])
                    ?? static::defaultOptionValueData($valueModels->get($row['option_value_id']), $row['option_value_id'], $optionId, $override);
            })->values()->all();

            return [
                'option_id'   => $optionId,
                'name'        => $existingOption['name'] ?? static::defaultOptionName($optionModels->get($optionId), $optionId, $override),
                'description' => $values,
            ];
        })->values()->all();

        $set('description.options_description', $rebuilt);
    }

    protected static function defaultOptionName(?Option $default, int $optionId, ?array $override): array
    {
        if (!$default) {
            return [];
        }

        $overrideGroup = collect($override)
            ->first(fn ($group) => (int) ($group['option_id'] ?? null) === $optionId);

        $nameOverride = $overrideGroup['name'] ?? [];
        $result = [];

        foreach ($default->getTranslations('name') as $locale => $name) {
            $result[$locale] = $nameOverride[$locale] ?? $name;
        }

        return $result;
    }

    protected static function defaultOptionValueData(?OptionValue $model, int $valueId, int $optionId, ?array $override): array
    {
        $default = $model?->toArray();
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

    // Render tab badge by JS
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