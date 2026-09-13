<?php


namespace App\Filament\Resources\Products\Schemas;

use App\Models\Catalog\Attribute;
use App\Models\Catalog\AttributeValue;
use App\Models\Catalog\ProductAttributeValue;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Context;


class AttributesTab
{
    public static function schema($storeId, $languages): array    
    {
        
        return [
            Repeater::make('productAttributes')
                ->relationship('productAttributes', modifyQueryUsing: fn ($query) => $query
                    ->where('store_id', $storeId)
                )
                ->schema([
                    // Hidden::make('store_id')->default($storeId),

                    Select::make('attribute_id')
                        ->options(fn () => static::attributeChoices($storeId))
                        ->afterStateUpdated(fn (Set $set) => $set('productAttributeValues', [])) // Also an array can be passed to create empty attribute value form TODO
                        ->searchable()
                        ->preload()
                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                        ->required()
                        ->live()
                        ->label(__('admin.catalog.attributes.fields.group')),

                    Repeater::make('productAttributeValues')
                        ->relationship('productAttributeValues')
                        ->schema([

                                // Product related data
                                Group::make([
                                     // Required
                                    Hidden::make('store_id')->default($storeId),
        
                                    // The form itself
                                    Select::make('attribute_value_id')
                                        ->options(fn (Get $get) => static::attributeValueChoices($get('../../attribute_id')))
                                        ->required()
                                        ->live()
                                        ->searchable()
                                        ->preload()
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                        ->afterStateUpdated(function ($state, Set $set, $livewire) {
                                            // Safely return if select is empty
                                            if (blank($state)) return;

                                            $defaultAttributeData = AttributeValue::find($state)?->toArray();
                                            if (!$defaultAttributeData) return;

                                            $productId = $livewire->getRecord()?->id;

                                            $overrideAttributeData = $productId
                                                ? ProductAttributeValue::query()
                                                    ->where('product_id', $productId)
                                                    ->where('attribute_value_id', $state)
                                                    ->first()
                                                    ?->toArray()
                                                : null;

                                            foreach ($defaultAttributeData['name'] as $locale => $name) {
                                                $set("name.{$locale}", $overrideAttributeData['name'][$locale] ?? $name);
                                            }
                                            foreach ($defaultAttributeData['description'] as $locale => $description) {
                                                $set("description.{$locale}", $overrideAttributeData['description'][$locale] ?? $description);
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
                        ->maxItems(fn (Get $get) => static::attributeValueChoices($get('attribute_id'))->count())
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
                ->maxItems(fn () => static::attributeChoices($storeId)->count())
                ->collapsible()
                ->collapsed(fn($operation) => $operation !== 'create')
                ->itemLabel(function (array $state) use ($storeId): ?string {
                    $attributeName = static::attributeChoices($storeId)->get($state['attribute_id'] ?? null);

                    if (blank($attributeName)) {
                        return null;
                    }

                    $valueChoices = static::attributeValueChoices($state['attribute_id'] ?? null);

                    $valueIds = filled($state['id'] ?? null)
                        ? ProductAttributeValue::where('product_attribute_id', $state['id'])->pluck('attribute_value_id')
                        : collect($state['productAttributeValues'] ?? [])->pluck('attribute_value_id');

                    $valueNames = $valueIds
                        ->filter()
                        ->map(fn ($id) => $valueChoices->get($id))
                        ->filter()
                        ->implode(', ');

                    return $valueNames !== '' ? "{$attributeName}: {$valueNames}" : $attributeName;
                })
                ->reorderable()
                ->orderColumn('sort_order')
                ->addActionLabel(__('admin.catalog.products.buttons.add_attribute'))
                ->label(__('admin.catalog.attributes.navigation_label'))
                ->hiddenLabel()
        ];
    }

    protected static function attributeValueDescriptionsForm($languages)
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
                                ->hiddenLabel()
                                ->columnSpanFull(),

                            RichEditor::make("description.{$language->locale}")
                                ->columnSpanFull()
                                ->placeholder(__('admin.catalog.attributes.fields.description'))
                                ->toolbarButtons([])
                                ->floatingToolbars([
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


    protected static function attributeValueChoices(?int $attributeId): Collection
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

    protected static function attributeChoices(int $storeId): Collection
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

    public static function label(): string
    {
        return __('admin.catalog.products.tabs.attributes');
    }
}