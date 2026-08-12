<?php

namespace App\Filament\Resources\FacetPages\Schemas;

use App\Domain\Catalog\FacetType;
use App\Filament\Schemas\LanguageTabs;
use App\Filament\Schemas\Tabs\DescriptionTab;
use App\Filament\Schemas\Tabs\FaqTab;
use App\Filament\Schemas\Tabs\FooterTab;
use App\Filament\Schemas\Tabs\HowToTab;
use App\Filament\Schemas\Tabs\ImagesTab;
use App\Models\Catalog\Attribute;
use App\Models\Catalog\AttributeValue;
use App\Models\Catalog\Category;
use App\Models\Catalog\Manufacturer;
use App\Models\Catalog\Option;
use App\Models\Catalog\OptionValue;
use App\Models\Catalog\Tag;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class FacetPageForm
{
    public static function configure(Schema $schema): Schema
    {
        $store = Filament::getTenant();
        $languages = $store->languages()->wherePivot('is_active', true)->get();
        return $schema
            ->components([

                Tabs::make('facet_page')
                    ->schema([
                        Tab::make(__('admin.common.tabs.content'))
                            ->schema([
                                Toggle::make('is_active')
                                    ->label(__('admin.catalog.facet_pages.fields.is_active'))
                                    ->default(true),

                                Repeater::make('facetIndex')
                                    ->relationship('facetIndex')
                                    ->table([
                                        TableColumn::make(__('admin.catalog.facet_pages.fields.facet_list'))->markAsRequired()
                                    ])
                                    ->schema([
                                        FusedGroup::make([
                                            Select::make('facet_type_id')
                                                ->label(__('admin.facet_pages.fields.facet_type'))
                                                ->options(FacetType::class)
                                                ->native(false)
                                                ->live()
                                                ->afterStateUpdated(function (Set $set) {
                                                    // Reset related inputs after change
                                                    $set('facet_value_id', null);
                                                    $set('facet_group_id', 0);
                                                })
                                                ->required()
                                                ->columnSpan(1),

                                            Select::make('facet_value_id')
                                                ->label(__('admin.facet_pages.fields.facet_value'))
                                                ->searchable()
                                                ->getSearchResultsUsing(function (string $search, Get $get) use ($store) {
                                                    $type = $get('facet_type_id');

                                                    if (blank($type)) {
                                                        return [];
                                                    }

                                                    $excluded = self::usedValueIds($get, $type);

                                                    return self::searchOptions($type, $store->id, $search, $excluded);
                                                })
                                                ->getOptionLabelUsing(function ($value, Get $get) use ($store) {
                                                    $type = $get('facet_type_id');
                                                    return blank($type) ? null : self::optionLabel($type, $store->id, (int) $value);
                                                })
                                                ->native(false)
                                                ->live()
                                                ->options(function (Get $get) use ($store) {
                                                    $type = $get('facet_type_id');

                                                    if (blank($type)) {
                                                        return [];
                                                    }
                                                    return self::searchOptions($type, $store->id, '', self::usedValueIds($get, $type));
                                                })
                                                ->disabled(fn (Get $get) => blank($get('facet_type_id')))
                                                ->native(false)
                                                ->live()
                                                ->disabled(fn (Get $get) => blank($get('facet_type_id')))
                                                ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                                                    $typeId = $get('facet_type_id');

                                                    $set('facet_group_id', filled($state) && filled($typeId)
                                                        ? self::groupIdFor($typeId, (int) $state)
                                                        : 0);
                                                })
                                                ->required()
                                                ->columnSpan(1),
                                        ])->columns(2),

                                        Hidden::make('facet_group_id')->default(0),
                                    ])
                                    ->reorderable(false)
                                    ->minItems(2)
                                    ->hiddenLabel()
                                    ->addActionLabel(__('admin.catalog.facet_pages.buttons.add_facet')),
                                LanguageTabs::make($languages, [
                                    [DescriptionTab::class, ['withSlug' => true]],
                                    FaqTab::class,
                                    HowToTab::class,
                                    FooterTab::class,

                                ])
                            ]),
                        Tab::make('images')
                            ->label(ImagesTab::label())
                            ->schema(ImagesTab::schema(['type' => 'category']))
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function searchOptions($type, int $storeId, string $search, array $excludedIds): array
    {
        $query = match ($type) {
            FacetType::Category     => Category::query()->where('store_id', $storeId),
            FacetType::Manufacturer => Manufacturer::query()->where('store_id', $storeId),
            FacetType::Attribute    => Attribute::query()->where('store_id', $storeId),
            FacetType::Option       => Option::query()->where('store_id', $storeId),
            FacetType::Tag          => Tag::query()->where('store_id', $storeId),
        };

        return $query
            ->whereRaw("name::text ilike ?", ["%{$search}%"])
            ->when($excludedIds !== [], fn ($q) => $q->whereNotIn('id', $excludedIds))
            ->limit(50)
            ->get()
            ->mapWithKeys(fn ($m) => [$m->id => $m->name])
            ->all();
    }

    public static function optionLabel($type, int $storeId, int $id): ?string
    {
        $model = match ($type) {
            FacetType::Category     => Category::find($id),
            FacetType::Manufacturer => Manufacturer::find($id),
            FacetType::Attribute    => Attribute::find($id),
            FacetType::Option       => Option::find($id),
            FacetType::Tag          => Tag::find($id),
        };

        return $model?->name;
    }

    private static function usedValueIds(Get $get, FacetType $type): array
    {
        return collect($get('../') ?? [])
            ->filter(fn ($item) => (string) ($item['facet_type_id'] ?? null) === (string) $type->value)
            ->pluck('facet_value_id')
            ->filter()
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    // public static function optionList($typeId, int $storeId): array
    // {
    //     return match ($typeId) {
    //         FacetType::Category => Category::query()
    //             ->where('store_id', $storeId)
    //             ->get()
    //             ->mapWithKeys(fn (Category $category) => [$category->id => $category->name])
    //             ->all(),

    //         FacetType::Manufacturer => Manufacturer::query()
    //             ->where('store_id', $storeId)
    //             ->get()
    //             ->mapWithKeys(fn (Manufacturer $manufacturer) => [$manufacturer->id => $manufacturer->name])
    //             ->all(),

    //         FacetType::Attribute => Attribute::query()
    //             ->where('store_id', $storeId)
    //             ->get()
    //             ->mapWithKeys(fn (Attribute $attribute) => [$attribute->id => $attribute->name])
    //             ->all(),

    //         FacetType::Option => Option::query()
    //             ->where('store_id', $storeId)
    //             ->get()
    //             ->mapWithKeys(fn (Option $option) => [$option->id => $option->name])
    //             ->all(),

    //         FacetType::Tag => Tag::query()
    //             ->where('store_id', $storeId)
    //             ->get()
    //             ->mapWithKeys(fn (Tag $tag) => [$tag->id => $tag->name])
    //             ->all(),

    //     };
    // }

    public static function groupIdFor(FacetType $type, int $valueId): int
    {
        return match ($type) {
            FacetType::Category     => Category::find($valueId)?->parent_id ?? 0, 
            FacetType::Manufacturer => Manufacturer::find($valueId)?->parent_id ?? 0, 
            FacetType::Tag          => 0,
            FacetType::Attribute    => AttributeValue::find($valueId)?->attribute_id ?? 0,
            FacetType::Option       => OptionValue::find($valueId)?->option_group_id ?? 0,
            default => 0,
        };
    }
}
