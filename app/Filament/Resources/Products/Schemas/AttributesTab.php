<?php


namespace App\Filament\Resources\Products\Schemas;

use App\Models\Catalog\Attribute;
use App\Models\Catalog\AttributeValue;
use Arr;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class AttributesTab
{
    public static function make($store, $languages): Tab
    {
        return Tab::make('attributes')
            ->badge(function (Get $get): ?int {
                // Count attributes by form state insead of quering the DB
                $groups = $get('description.attributes_description') ?? [];
                $count  = collect($groups)->sum(fn ($group) => count($group['description'] ?? []));
                return $count ?: null;
            })
            ->schema([
                Repeater::make('attributes_description')
                    ->statePath('description.attributes_description')
                    ->schema([

                        Select::make('attribute_id')
                            ->label(__('admin.catalog.products.tabs.attributes.labels.attribute'))
                            ->noOptionsMessage(__('admin.catalog.products.tabs.attributes.placeholders.no_attributes'))
                            ->searchPrompt(__('admin.catalog.products.tabs.attributes.placeholders.search_attributes'))
                            ->placeholder(__('admin.catalog.products.tabs.attributes.placeholders.search_attributes'))
                            ->searchingMessage(__('admin.catalog.products.tabs.attributes.placeholders.searching_attributes'))
                            ->options(fn() => Attribute::attributeChoices($store->id))
                            ->afterStateUpdated(function (Set $set, Get $get, $state, $livewire) use ($store) {
                                $set('values_description', []);

                                if (blank($state))
                                    return;

                                $default = Attribute::find($state);
                                if (!$default)
                                    return;

                                $record = $livewire->getRecord();
                                $override = $record
                                    ? $record->descriptions()->where('store_id', $store->id)->first()?->attributes_description
                                    : null;

                                $overrideGroup = collect($override)->first(fn($g) => (int) ($g['attribute_id'] ?? null) === (int) $state);
                                $nameOverride = $overrideGroup['name'] ?? [];

                                foreach ($default->getTranslations('name') as $locale => $name) {
                                    $set("name.{$locale}", $nameOverride[$locale] ?? $name);
                                }
                            })
                            ->searchable()
                            ->preload()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->required()
                            ->live()
                            ->label(__('admin.catalog.attributes.fields.group')),

                        FusedGroup::make(
                            collect($languages)->map(
                                fn($language) =>
                                TextInput::make("name.{$language->locale}")
                                    ->live(onBlur: true)
                                    ->prefix($language->locale)
                                    ->required(fn(Get $get) => $get('attribute_id') !== null)
                                    ->visible(fn(Get $get) => $get('attribute_id') !== null)
                                    ->hiddenLabel()
                                    ->label(__('admin.catalog.attributes.fields.group_name'))
                            )->all()
                        )
                        ->helperText(__('admin.catalog.attributes.helpers.group_name')),

                        Repeater::make('description')
                            ->schema([
                                Group::make([
                                    // The form itself
                                    Select::make('attribute_value_id')
                                        ->label(__('admin.catalog.products.tabs.attributes.labels.attribute_value'))
                                        ->noOptionsMessage(__('admin.catalog.products.tabs.attributes.placeholders.no_attribute_values'))
                                        ->searchPrompt(__('admin.catalog.products.tabs.attributes.placeholders.search_attribute_values'))
                                        ->placeholder(__('admin.catalog.products.tabs.attributes.placeholders.search_attribute_values'))
                                        ->searchingMessage(__('admin.catalog.products.tabs.attributes.placeholders.searching_attribute_values'))
                                        ->options(fn(Get $get) => AttributeValue::attributeValueChoices($get('../../attribute_id')))
                                        ->required()
                                        ->live()
                                        ->searchable()
                                        ->preload()
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                        ->afterStateUpdated(function ($state, Set $set, Get $get, $livewire) use ($store) {
                                            if (blank($state))
                                                return;

                                            $default = AttributeValue::find($state)?->toArray();
                                            if (!$default)
                                                return;

                                            $attributeId = $get('../../attribute_id');
                                            $record = $livewire->getRecord();

                                            $override = $record
                                                ? $record->descriptions()->where('store_id', $store->id)->first()?->attributes_description
                                                : null;

                                            $overrideGroup = collect($override)->first(fn($g) => (int) ($g['attribute_id'] ?? null) === (int) $attributeId);
                                            $valueOverride = collect($overrideGroup['description'] ?? [])
                                                ->first(fn($v) => (int) ($v['attribute_value_id'] ?? null) === (int) $state) ?? [];

                                            foreach ($default['name'] as $locale => $name) {
                                                $set("name.{$locale}", $valueOverride['name'][$locale] ?? $name);
                                            }
                                            foreach ($default['description'] as $locale => $description) {
                                                $set("description.{$locale}", $valueOverride['description'][$locale] ?? $description);
                                            }
                                        }),
                                ])->columnSpan(1),

                                // Attribute value related data
                                Group::make([
                                    ...self::attributeValueDescriptionsForm($languages)
                                ])->columnSpan(4),
                            ])
                            ->minItems(1)
                            ->default([])
                            ->maxItems(fn(Get $get) => AttributeValue::attributeValueChoices($get('attribute_id'))->count())
                            ->collapsible()
                            // ->collapsed(fn($operation) => $operation !== 'create')
                            ->itemLabel(fn(array $state): ?string => static::itemLabelText($state))
                            ->reorderable()
                            ->orderColumn('sort_order')
                            ->columns(5)
                            ->addActionLabel(__('admin.catalog.products.tabs.attributes.buttons.add_attribute_value'))
                            ->addActionAlignment('end')
                            ->label(__('admin.catalog.attributes.fields.values'))
                    ])
                    ->defaultItems(0)
                    ->maxItems(fn() => Attribute::attributeChoices($store->id)->count())
                    ->collapsible()
                    // ->collapsed(fn($operation) => $operation !== 'create')
                    ->itemLabel(fn(array $state): ?string => static::groupItemLabel($state))
                    ->reorderable()
                    ->orderColumn('sort_order')
                    ->addActionLabel(__('admin.catalog.products.tabs.attributes.buttons.add_attribute'))
                    ->label(__('admin.catalog.products.tabs.attributes.label'))
                    ->belowLabel(__('admin.catalog.products.tabs.attributes.helpers.label'))
                    ->hiddenLabel()
                    ->afterStateUpdatedJs(static::badgeUpdateJs(
                        "Object.values(\$get('description.attributes_description') ?? {}).reduce((n, g) => n + Object.keys(g.description ?? {}).length, 0)"
                    ))
                    ->live()
                    ->partiallyRenderComponentsAfterStateUpdated(['description.attributes_description'])
            ]);
    }

    private static function attributeValueDescriptionsForm($languages)
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
                                ->label(__('admin.catalog.attributes.fields.attribute_name'))
                                ->placeholder(__('admin.catalog.attributes.fields.attribute_name'))
                                ->hiddenLabel(),

                            RichEditor::make("description.{$language->locale}")
                                ->columnSpanFull()
                                ->placeholder(__('admin.catalog.attributes.fields.description'))
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

    protected static function itemLabelText(array $state): ?string
    {
        $name = array_filter($state['name'] ?? []);
        if (blank($name)) return null;

        return $name[app()->getLocale()] ?? Arr::first($name);
    }

    protected static function groupItemLabel(array $state): ?string
    {
        $groupName = static::itemLabelText($state);
        if (blank($groupName)) return null;

        $locale = app()->getLocale();
        $valueNames = collect($state['description'] ?? [])
            ->map(fn ($v) => static::itemLabelText($v))
            ->filter()
            ->implode(', ');

        return $valueNames !== '' ? "{$groupName}: {$valueNames}" : $groupName;
    }
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