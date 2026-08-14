<?php

namespace App\Filament\Resources\FacetPages\Schemas;

use App\Domain\Catalog\FacetType;
use App\Filament\Schemas\LanguageTabs;
use App\Filament\Schemas\Tabs\{DescriptionTab, FaqTab, FooterTab, HowToTab, ImagesTab};
use App\Models\Catalog\{AttributeValue, Category, Manufacturer, OptionValue, Tag};
use App\Models\Global\Store;
use Filament\Actions\Action;
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
use Filament\Support\Enums\Alignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Facet page
 * Allows to select ONE categoty and/or ONE manufacturer and unlimited number of other facets
 * Checks facets set duplicate
 */

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
                                            // Facet type selector: category, manufacturer, option, attribute, ect.
                                            Select::make('facet_type_id')
                                                ->label(__('admin.catalog.facet_pages.fields.facet_type'))
                                                ->options(FacetType::class)
                                                ->native(false)
                                                ->live()
                                                ->afterStateUpdated(function (Set $set) {
                                                    // Reset related inputs after change
                                                    $set('facet_value_id', null);
                                                    $set('facet_group_id', 0);
                                                })
                                                ->required()
                                                ->validationMessages([
                                                    'required' => __('admin.catalog.facet_pages.errors.facet_type_required')
                                                ])
                                                // Allow only one category and one manufacturer selection
                                                ->disableOptionWhen(function ($value, Get $get): bool {
                                                    
                                                    if (!in_array((int) $value, [FacetType::Category->value, FacetType::Manufacturer->value], true)) {
                                                        return false;
                                                    }

                                                    $ownType = self::toFacetType($get('facet_type_id'));

                                                    // Skip own value
                                                    if ($ownType?->value === (int) $value) {
                                                        return false;
                                                    }

                                                    $usedElsewhere = collect($get('../') ?? [])
                                                        ->map(fn($item) => self::toFacetType($item['facet_type_id'] ?? null)?->value)
                                                        ->filter()
                                                        ->all();

                                                    return in_array((int) $value, $usedElsewhere, true);
                                                })
                                                ->columnSpan(1),

                                            Select::make('facet_value_id')
                                                ->label(__('admin.catalog.facet_pages.fields.facet_value'))
                                                ->searchable()
                                                ->getSearchResultsUsing(function (string $search, Get $get) use ($store) {
                                                    $type = $get('facet_type_id');
                                                    if (blank($type)) {
                                                        return [];
                                                    }
                                                    // Load 50 options using search string excluding already selected facet_value_id
                                                    return self::searchOptions($type, $store->id, $search, self::usedValueIds($get, $type));
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
                                                    // Preload first 50 options excluding already selected facet_value_id
                                                    return self::searchOptions($type, $store->id, '', self::usedValueIds($get, $type));
                                                })
                                                ->disabled(fn (Get $get) => blank($get('facet_type_id'))) // Disable if facet_type_id is not selected
                                                ->native(false)
                                                ->live()
                                                ->afterStateUpdated(function (Set $set, Get $get, $component, $livewire, ?string $state) use ($store) {
                                                    $typeId = $get('facet_type_id');
                                                    // Set facet_group_id hidden input
                                                    $set('facet_group_id', filled($state) && filled($typeId) ? self::groupIdFor($typeId, (int) $state) : 0);
                                                    self::validateCombinationLive($get, $component, $livewire, $store);
                                                })
                                                ->required()
                                                ->validationMessages([
                                                    'required' => __('admin.catalog.facet_pages.errors.facet_value_required'),
                                                ])
                                                ->columnSpan(1),
                                                Hidden::make('facet_group_id')->default(0),
                                            ])->columns(2),

                                    ])
                                    ->rules([
                                        function (?Model $record) use ($store) {
                                            return function (string $attribute, $value, \Closure $fail) use ($record, $store) {

                                                $facets = collect($value ?? [])
                                                    ->filter(fn($i) => filled($i['facet_type_id'] ?? null) && filled($i['facet_value_id'] ?? null))
                                                    ->map(fn($i) => [
                                                        'facet_type_id' => $i['facet_type_id'] instanceof FacetType
                                                            ? $i['facet_type_id']->value
                                                            : (int) $i['facet_type_id'],
                                                        'facet_group_id' => (int) ($i['facet_group_id'] ?? 0),
                                                        'facet_value_id' => (int) $i['facet_value_id'],
                                                    ])
                                                    ->all();

                                                // Not more than one category or one manufacturer
                                                foreach ([FacetType::Category->value => 'category', FacetType::Manufacturer->value => 'manufacturer'] as $typeValue => $key) {
                                                    $count = collect($facets)->where('facet_type_id', $typeValue)->count();

                                                    if ($count > 1) {
                                                        $fail(__("admin.catalog.facet_pages.errors.too_many_root"));
                                                        return;
                                                    }
                                                }

                                                // At least one category or one manufacturer
                                                $hasRootFacet = collect($facets)->contains(
                                                    fn($f) => in_array($f['facet_type_id'], [FacetType::Category->value, FacetType::Manufacturer->value], true)
                                                );

                                                if (!$hasRootFacet) {
                                                    $fail(__('admin.catalog.facet_pages.errors.root_facet_not_selected'));
                                                    return;
                                                }

                                                // Duplicates
                                                $duplicateId = self::queryFilterPageByFacets($store->id, $facets, excludePageId: $record?->id);

                                                if ($duplicateId) {
                                                    $fail(__('admin.catalog.facet_pages.errors.duplicate_combination'));
                                                }
                                            };
                                        },
                                    ])
                                    ->reorderable(false)
                                    ->minItems(2)
                                    ->hiddenLabel()
                                    ->addActionAlignment(Alignment::End)
                                    ->addAction(
                                        fn(Action $action) =>
                                        $action
                                            ->color('success')
                                            ->icon('heroicon-m-plus')
                                            ->label(__('admin.catalog.facet_pages.buttons.add_facet'))
                                    ),
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
            FacetType::Attribute    => AttributeValue::query()->where('store_id', $storeId),
            FacetType::Option       => OptionValue::query()->where('store_id', $storeId),
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

    /**
     * Get option labels for live seach
     */
    public static function optionLabel($type, int $storeId, int $id): ?string
    {
        $model = match ($type) {
            FacetType::Category     => Category::find($id),
            FacetType::Manufacturer => Manufacturer::find($id),
            FacetType::Attribute    => AttributeValue::find($id),
            FacetType::Option       => OptionValue::find($id),
            FacetType::Tag          => Tag::find($id),
        };

        return $model?->name;
    }


    /**
     * Exclude already used facet_value_id, group by facet_value_id
     */
    private static function usedValueIds(Get $get, FacetType $type): array
    {
        return collect($get('../') ?? [])
            ->filter(fn ($item) => (string) ($item['facet_type_id'] ?? null) === (string) $type->value)
            ->pluck('facet_value_id')
            ->filter()
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /**
     *  Get facet_group_id which is parent entity for facet_value_id
     */
    private static function groupIdFor(FacetType $type, int $valueId): int
    {
        return match ($type) {
            FacetType::Category     => Category::find($valueId)?->parent_id ?? 0, 
            FacetType::Manufacturer => Manufacturer::find($valueId)?->parent_id ?? 0, 
            FacetType::Attribute    => AttributeValue::find($valueId)?->attribute_id ?? 0,
            FacetType::Option       => OptionValue::find($valueId)?->option_group_id ?? 0,
            FacetType::Tag          => 0, // Tag has no parent, so always 0
            default => 0,
        };
    }

    /**
     * Query filetr pages by facet combination
     * Allows to exclude existing pagest with the same facets
     */
    private static function queryFilterPageByFacets(int $storeId, array $facets, ?int $excludePageId = null): ?int
    {
        $facets = collect($facets)->unique(
            fn ($f) => "{$f['facet_type_id']}:{$f['facet_group_id']}:{$f['facet_value_id']}"
        )->values();

        if ($facets->isEmpty()) {
            return null;
        }

        $conditions = [];
        $bindings   = [];

        foreach ($facets as $f) {
            $conditions[] = '(facet_type_id = ? AND facet_group_id = ? AND facet_value_id = ?)';
            array_push($bindings, $f['facet_type_id'], $f['facet_group_id'], $f['facet_value_id']);
        }

        $sql = "
            SELECT facet_page_id
            FROM facet_page_index
            WHERE store_id = ?
              AND (" . implode(' OR ', $conditions) . ")
            GROUP BY facet_page_id
            HAVING COUNT(*) = ?
               AND COUNT(*) = (
                   SELECT COUNT(*)
                   FROM facet_page_index fi2
                   WHERE fi2.facet_page_id = facet_page_index.facet_page_id
               )
        ";

        $rows = DB::select($sql, [$storeId, ...$bindings, $facets->count()]);

        return collect($rows)
            ->pluck('facet_page_id')
            ->reject(fn ($id) => $excludePageId && $id === $excludePageId)
            ->first();
    }

    /**
     * Check facets combination live using queryFilterPageByFacets
     */
    private static function validateCombinationLive(Get $get, $component, $livewire, Store $store): void
    {
        $path = $component->getStatePath();
        $livewire->resetErrorBag($path);

        $facets = collect($get('../') ?? [])
            ->filter(fn ($i) => filled($i['facet_type_id'] ?? null) && filled($i['facet_value_id'] ?? null))
            ->map(fn ($i) => [
                'facet_type_id'  => $i['facet_type_id'] instanceof FacetType ? $i['facet_type_id']->value : (int) $i['facet_type_id'],
                'facet_group_id' => (int) ($i['facet_group_id'] ?? 0),
                'facet_value_id' => (int) $i['facet_value_id'],
            ])
            ->all();

        if ($facets === []) {
            return;
        }

        // $livewire - EditRecord on page edit (getRecord() exists), CreateRecord on record create (getRecord() does not exists)
        $excludeId = method_exists($livewire, 'getRecord') ? $livewire->getRecord()?->id : null;

        $duplicateId = self::queryFilterPageByFacets($store->id, $facets, excludePageId: $excludeId);

        if ($duplicateId) {
            $livewire->addError($path, __('admin.catalog.facet_pages.errors.duplicate_combination'));
        }
    }

    private static function toFacetType(mixed $value): ?FacetType
    {
        if ($value instanceof FacetType) {
            return $value;
        }

        return blank($value) ? null : FacetType::from((int) $value);
    }
}
