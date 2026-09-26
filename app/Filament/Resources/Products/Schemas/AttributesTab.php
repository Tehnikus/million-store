<?php


namespace App\Filament\Resources\Products\Schemas;

use App\Models\Catalog\Attribute;
use App\Models\Catalog\AttributeValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Context;

class AttributesTab
{
    public static function make($store, $languages): Tab
    {
        return Tab::make('attributes')
            ->badge(fn($record) => self::countProductAttributes($record, $store))
            ->schema([
                Repeater::make('attributes_description')
                    ->statePath('description.attributes_description')
                    ->schema([

                        Select::make('attribute_id')
                            ->options(fn() => static::attributeChoices($store->id))
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

                        Repeater::make('description')
                            ->schema([
                                Group::make([
                                    // The form itself
                                    Select::make('attribute_value_id')
                                        ->options(fn(Get $get) => static::attributeValueChoices($get('../../attribute_id')))
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
                            ->maxItems(fn(Get $get) => static::attributeValueChoices($get('attribute_id'))->count())
                            ->collapsible()
                            ->collapsed(fn($operation) => $operation !== 'create')
                            ->itemLabel(function (array $state, Get $get): ?string {
                                $attributeId = $get('attribute_id');
                                return static::attributeValueChoices($attributeId)->get($state['attribute_value_id'] ?? null);
                            })
                            ->reorderable()
                            ->orderColumn('sort_order')
                            ->columns(5)
                            ->addActionLabel(__('admin.catalog.products.buttons.add_attribute_value'))
                            ->addActionAlignment('end')
                            ->label(__('admin.catalog.attributes.fields.values'))
                    ])
                    ->defaultItems(0)
                    ->maxItems(fn() => static::attributeChoices($store->id)->count())
                    ->collapsible()
                    // ->collapsed(fn($operation) => $operation !== 'create')
                    ->itemLabel(fn(array $state): ?string => static::groupItemLabel($state))
                    ->reorderable()
                    ->orderColumn('sort_order')
                    ->addActionLabel(__('admin.catalog.products.buttons.add_attribute'))
                    ->label(__('admin.catalog.attributes.navigation_label'))
                    ->hiddenLabel()
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
                                ->required()
                                ->maxLength(255)
                                ->prefix($language->locale)
                                ->label(__('admin.catalog.attributes.fields.attribute_name'))
                                ->placeholder(__('admin.catalog.attributes.fields.attribute_name'))
                                ->hiddenLabel(),

                            RichEditor::make("description.{$language->locale}")
                                ->columnSpanFull()
                                ->placeholder(__('admin.catalog.attributes.fields.description'))
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

    private static function attributeValueChoices(?int $attributeId): Collection
    {
        if (blank($attributeId)) {
            return collect();
        }

        $key = "attribute_value_choices.{$attributeId}";

        if (Context::has($key)) {
            return collect(Context::get($key));
        }

        $choices = AttributeValue::query()
            ->where('attribute_id', $attributeId)
            ->where('is_active', true)
            ->pluck('name', 'id');

        Context::add($key, $choices->all());

        return $choices;
    }

    private static function attributeChoices(int $storeId): Collection
    {
        $key = "attribute_choices.{$storeId}";

        if (Context::has($key)) {
            return collect(Context::get($key));
        }

        $choices = Attribute::query()
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->pluck('name', 'id');

        Context::add($key, $choices->all());

        return $choices;
    }

    private static function countProductAttributes($record, $store): mixed
    {
        if (!$record) return null;
        $badge = 0;

        $description = $record->descriptions()
            ->where('store_id', $store->id)
            ->first();

        $badge = collect($description?->attributes_description ?? [])
            ->sum(fn ($group) => count($group['description'] ?? []));

        return $badge !== 0 ? $badge : null;
    }

}