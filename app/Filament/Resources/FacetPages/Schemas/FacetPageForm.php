<?php

namespace App\Filament\Resources\FacetPages\Schemas;

use App\Domain\Catalog\FacetType;
use App\Filament\Schemas\Tabs\{DescriptionTab, FaqTab, FooterTab, HowToTab, ImagesTab};
use App\Models\Catalog\{AttributeValue, Category, FacetPage, FacetPageIndex, Manufacturer, OptionValue, Tag};
use App\Models\Global\Store;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Facet page
 * Allows to select ONE category and/or ONE manufacturer and unlimited number of other facets
 * Checks facets set duplicate
 */

class FacetPageForm
{
    public static function configure(Schema $schema): Schema
    {
        $store      = Filament::getTenant();
        $languages  = $store->activeLanguages();

        return $schema
            ->components([
                Tabs::make('facet_page')
                    ->schema([
                        Tab::make(__('admin.common.tabs.content'))
                            ->schema([
                                Toggle::make('is_active')
                                    ->label(__('admin.catalog.facet_pages.fields.is_active'))
                                    ->default(true),

                                // Parent facet. Must be category or manufacturer type. Always required
                                Fieldset::make(__('admin.catalog.facet_pages.fields.root_facet'))
                                    ->schema([
                                        FusedGroup::make([
                                            Select::make('root_facet_type_id')
                                                ->label(__('admin.catalog.facet_pages.fields.facet_type'))
                                                ->options(
                                                    collect(FacetType::cases())
                                                        ->filter->canBeRoot()
                                                        ->mapWithKeys(fn($t) => [$t->value => $t->getLabel()])
                                                )
                                                ->native(false)
                                                ->live()
                                                ->afterStateUpdated(function (Set $set) {
                                                    $set('root_facet_value_id', null);
                                                    $set('root_facet_group_id', 0);
                                                })
                                                ->required()
                                                ->columnSpan(1),

                                            Select::make('root_facet_value_id')
                                                ->label(__('admin.catalog.facet_pages.fields.facet_value'))
                                                ->searchable()
                                                ->getSearchResultsUsing(function (string $search, Get $get) use ($store) {
                                                    $type = self::toFacetType($get('root_facet_type_id'));
                                                    return $type ? self::searchOptions($type, $store->id, $search, []) : [];
                                                })
                                                ->getOptionLabelUsing(function ($value, Get $get) use ($store) {
                                                    $type = self::toFacetType($get('root_facet_type_id'));
                                                    return $type ? self::optionLabel($type, $store->id, (int) $value) : null;
                                                })
                                                ->options(function (Get $get) use ($store) {
                                                    $type = self::toFacetType($get('root_facet_type_id'));
                                                    return $type ? self::searchOptions($type, $store->id, '', []) : [];
                                                })
                                                ->native(false)
                                                ->live()
                                                ->disabled(fn(Get $get) => blank($get('root_facet_type_id')))
                                                ->afterStateUpdated(function (Set $set, Get $get, $component, $livewire, ?string $state) use ($store) {
                                                    $type = self::toFacetType($get('root_facet_type_id'));
                                                    $set('root_facet_group_id', filled($state) && $type ? self::groupIdFor($type, (int) $state) : 0);

                                                    $facets    = self::currentFacetSet($get, $get('facetIndex') ?? []);
                                                    $excludeId = method_exists($livewire, 'getRecord') ? $livewire->getRecord()?->id : null;

                                                    self::checkCombinationDuplicate($facets, $component->getStatePath(), $livewire, $store, $excludeId);
                                                })
                                                ->required()
                                                ->columnSpan(1),
                                        ])
                                        ->columns(2)
                                        ->columnSpanFull(),

                                        Hidden::make('root_facet_group_id')->default(0),
                                        
                                        Text::make(__('admin.catalog.facet_pages.helpers.root_facet'))
                                    ])
                                    ->columns(1),

                                // Other facets, only non-root 
                                Fieldset::make(__('admin.catalog.facet_pages.fields.additional_facets'))
                                    ->schema([

                                        Repeater::make('facetIndex')
                                            ->relationship(
                                                name: 'facetIndex',
                                                modifyQueryUsing: fn(Builder $query) => $query->where('is_root', false),
                                            )
                                            ->table([
                                                TableColumn::make(__('admin.catalog.facet_pages.fields.facet_list'))->markAsRequired()
                                            ])
                                            ->schema([
                                                FusedGroup::make([
                                                    Select::make('facet_type_id')
                                                        ->label(__('admin.catalog.facet_pages.fields.facet_type'))
                                                        ->options(
                                                            collect(FacetType::cases())
                                                                ->reject->canBeRoot()
                                                                ->mapWithKeys(fn($t) => [$t->value => $t->getLabel()])
                                                        )
                                                        ->native(false)
                                                        ->live()
                                                        ->afterStateUpdated(function (Set $set) {
                                                            $set('facet_value_id', null);
                                                            $set('facet_group_id', 0);
                                                        })
                                                        ->required()
                                                        ->disableOptionWhen(function ($value, Get $get): bool {
                                                            $candidateType = self::toFacetType($value);
        
                                                            if (! $candidateType?->isSingleton()) {
                                                                return false;
                                                            }
                                                
                                                            $ownType = self::toFacetType($get('facet_type_id'));
        
                                                            if ($ownType?->value === $candidateType->value) {
                                                                return false;
                                                            }
        
                                                            $usedElsewhere = collect($get('../') ?? [])
                                                                ->map(fn($item) => self::toFacetType($item['facet_type_id'] ?? null)?->value)
                                                                ->filter()
                                                                ->all();
        
                                                            return in_array($candidateType->value, $usedElsewhere, true);
                                                        })
                                                        ->columnSpan(1),
        
                                                    Select::make('facet_value_id')
                                                        ->label(__('admin.catalog.facet_pages.fields.facet_value'))
                                                        ->searchable()
                                                        ->getSearchResultsUsing(function (string $search, Get $get) use ($store) {
                                                            $type = self::toFacetType($get('facet_type_id'));
                                                            return $type ? self::searchOptions($type, $store->id, $search, self::usedValueIds($get, $type)) : [];
                                                        })
                                                        ->getOptionLabelUsing(function ($value, Get $get) use ($store) {
                                                            $type = self::toFacetType($get('facet_type_id'));
                                                            return $type ? self::optionLabel($type, $store->id, (int) $value) : null;
                                                        })
                                                        ->options(function (Get $get) use ($store) {
                                                            $type = self::toFacetType($get('facet_type_id'));
                                                            return $type ? self::searchOptions($type, $store->id, '', self::usedValueIds($get, $type)) : [];
                                                        })
                                                        ->native(false)
                                                        ->live()
                                                        ->disabled(fn(Get $get) => blank($get('facet_type_id'))) // Disable if facet_type_id is not selected
                                                        ->afterStateUpdated(function (Set $set, Get $get, $component, $livewire, ?string $state) use ($store) {
                                                            $type = self::toFacetType($get('facet_type_id'));
                                                            $set('facet_group_id', filled($state) && $type ? self::groupIdFor($type, (int) $state) : 0);
        
                                                            $facets    = self::currentFacetSet($get, $get('../') ?? []);
                                                            $excludeId = method_exists($livewire, 'getRecord') ? $livewire->getRecord()?->id : null;
        
                                                            self::checkCombinationDuplicate($facets, $component->getStatePath(), $livewire, $store, $excludeId);
                                                        })
                                                        ->required()
                                                        ->validationMessages([
                                                            'required' => __('admin.catalog.facet_pages.errors.facet_value_required'),
                                                        ])
                                                        ->columnSpan(1),
        
        
                                                ])->columns(2),
                                                Hidden::make('facet_group_id')->default(0),
                                            ])
                                            ->rules([
                                                function (?Model $record, Get $get) use ($store) {
                                                    return function (string $attribute, $value, \Closure $fail) use ($record, $store, $get) {
                                                        $facets = collect($value ?? [])
                                                            ->filter(fn($i) => filled($i['facet_type_id'] ?? null) && filled($i['facet_value_id'] ?? null))
                                                            ->map(fn($i) => [
                                                                'facet_type_id' => $i['facet_type_id'] instanceof FacetType ? $i['facet_type_id']->value : (int) $i['facet_type_id'],
                                                                'facet_group_id' => (int) ($i['facet_group_id'] ?? 0),
                                                                'facet_value_id' => (int) $i['facet_value_id'],
                                                            ])
                                                            ->all();
        
                                                        // Combination check for duplicates - the uniqueness of the entire set,
                                                        // "exactly one root" and "no more than one category/manufacturer"
                                                        // are already guaranteed by the form structure (a single root Select + required)
                                                        $rootType = self::toFacetType($get('root_facet_type_id'));
                                                        if ($rootType && filled($get('root_facet_value_id'))) {
                                                            $facets[] = [
                                                                'facet_type_id' => $rootType->value,
                                                                'facet_group_id' => (int) ($get('root_facet_group_id') ?? 0),
                                                                'facet_value_id' => (int) $get('root_facet_value_id'),
                                                            ];
                                                        }
        
                                                        $duplicateId = self::queryFilterPageByFacets($store->id, $facets, excludePageId: $record?->id);
        
                                                        if ($duplicateId) {
                                                            $fail(__('admin.catalog.facet_pages.errors.duplicate_combination'));
                                                        }
                                                    };
                                                },
                                            ])
                                            ->minItems(1)
                                            ->reorderable(false)
                                            ->hiddenLabel()
                                            ->addActionLabel(__('admin.catalog.facet_pages.buttons.add_facet'))
                                            ->addActionAlignment('end'),
                                        Text::make(__('admin.catalog.facet_pages.helpers.additional_facets'))
                                    ])
                                    ->columns(1),

                                Tabs::make('languages')
                                    ->schema([
                                        ...collect($languages)->map(fn($language) =>
                                            Tab::make($language->locale)
                                                ->label("{$language->name}")
                                                ->schema([
                                                    Tabs::make("content.{$language->locale}")
                                                        ->schema([
                                                            DescriptionTab::make($language, ['withSlug' => true]),
                                                            FaqTab::make($language),
                                                            HowToTab::make($language),
                                                            FooterTab::make($language),
                                                        ])

                                                ])
                                        )
                                    ])
                            ]),
                        ImagesTab::make($store, $languages, ['type' => 'facet_page'])
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Search options for facet value id
     * @param mixed     $type FacetType::Case
     * @param int       $storeId
     * @param string    $search
     * @param array     $excludedIds
     * @return array
     */
    public static function searchOptions($type, int $storeId, string $search, array $excludedIds): array
    {
        $modelClass = $type->modelClass();

        if (!$modelClass) {
            return [$type->getLabel()];
        }

        return $modelClass::query()
            ->where('store_id', $storeId)
            ->whereRaw("name::text ilike ?", ["%{$search}%"])
            ->when($excludedIds !== [], fn($q) => $q->whereNotIn('id', $excludedIds))
            ->limit(50)
            ->get()
            ->mapWithKeys(fn($m) => [$m->id => $m->name])
            ->all();
    }

    /**
     * Get option labels for live search
     */
    public static function optionLabel($type, int $storeId, int $id): ?string
    {
        $modelClass = $type->modelClass();
        $model = $modelClass ? $modelClass::where('store_id', $storeId)->find($id) : null;

        return $model?->name ?? $type->getLabel();
    }

    /**
     * Separates root_facet_* (UI service fields, not facet_pages columns) from the rest of the form data
     */
    public static function extractRootFacet(array $data): array
    {
        $rootType = self::toFacetType($data['root_facet_type_id'] ?? null);
        $rootValueId = $data['root_facet_value_id'] ?? null;

        $root = ($rootType && filled($rootValueId))
            ? [
                'facet_type_id' => $rootType->value,
                'facet_group_id' => (int) ($data['root_facet_group_id'] ?? 0),
                'facet_value_id' => (int) $rootValueId,
            ]
            : null;

        unset($data['root_facet_type_id'], $data['root_facet_value_id'], $data['root_facet_group_id']);

        return [$data, $root];
    }

    /**
     * Writes/updates the single is_root=true row of the page.
     * Separate from the relationship repeater (which only manages is_root=false)
     * this way each synchronization mechanism is responsible only for its own portion of the facet_page_index, without interfering.
     */
    public static function saveRootFacet(FacetPage $page, ?array $root): void
    {
        if (!$root) {
            throw new \RuntimeException("FacetPage #{$page->id} saved without a root facet.");
        }

        FacetPageIndex::updateOrCreate(
            ['facet_page_id' => $page->id, 'is_root' => true],
            $root,
        );
    }

    /**
     * Exclude already used facet_value_id, group by facet_value_id
     */
    private static function usedValueIds(Get $get, FacetType $type): array
    {
        return collect($get('../') ?? [])
            ->filter(fn($item) => (string) ($item['facet_type_id'] ?? null) === (string) $type->value)
            ->pluck('facet_value_id')
            ->filter()
            ->map(fn($v) => (int) $v)
            ->all();
    }

    /**
     *  Get facet_group_id which is parent entity for facet_value_id
     */
    private static function groupIdFor(FacetType $type, int $valueId): int
    {
        $modelClass = $type->modelClass();
        $column = $type->groupIdColumn();

        if (!$modelClass || !$column) {
            return 0;
        }

        return $modelClass::find($valueId)?->{$column} ?? 0;
    }

    /**
     * Query filetr pages by facet combination
     * Allows to exclude existing pagest with the same facets
     */
    private static function queryFilterPageByFacets(int $storeId, array $facets, ?int $excludePageId = null): ?int
    {
        $facets = collect($facets)->unique(
            fn($f) => "{$f['facet_type_id']}:{$f['facet_group_id']}:{$f['facet_value_id']}"
        )->values();

        if ($facets->isEmpty()) {
            return null;
        }

        $conditions = [];
        $bindings = [];

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
            ->reject(fn($id) => $excludePageId && $id === $excludePageId)
            ->first();
    }

    /**
     * Check facets combination live using queryFilterPageByFacets
     * Checks a set of facets for duplicates and reports an error to a specific path.
     * It doesn't know WHERE the set came from, the calling code decides for itself how to assemble it.
     */
    private static function checkCombinationDuplicate(array $facets, string $path, $livewire, Store $store, ?int $excludeId): void
    {
        $livewire->resetErrorBag($path);

        if ($facets === []) {
            return;
        }

        $duplicateId = self::queryFilterPageByFacets($store->id, $facets, excludePageId: $excludeId);

        if ($duplicateId) {
            $livewire->addError($path, __('admin.catalog.facet_pages.errors.duplicate_combination'));
        }
    }

    /**
     * Collects a FULL set of page facets (root + repeater)
     *  unified logic, used in both ->rules() and live checks to ensure consistency.
     */
    private static function currentFacetSet(Get $get, array $repeaterItems): array
    {
        $facets = collect($repeaterItems)
            ->filter(fn ($i) => filled($i['facet_type_id'] ?? null) && filled($i['facet_value_id'] ?? null))
            ->map(fn ($i) => [
                'facet_type_id'  => self::toFacetType($i['facet_type_id'])->value,
                'facet_group_id' => (int) ($i['facet_group_id'] ?? 0),
                'facet_value_id' => (int) $i['facet_value_id'],
            ])
            ->all();

        $rootType = self::toFacetType($get('root_facet_type_id'));

        if ($rootType && filled($get('root_facet_value_id'))) {
            $facets[] = [
                'facet_type_id'  => $rootType->value,
                'facet_group_id' => (int) ($get('root_facet_group_id') ?? 0),
                'facet_value_id' => (int) $get('root_facet_value_id'),
            ];
        }

        return $facets;
    }

    private static function toFacetType(mixed $value): ?FacetType
    {
        if ($value instanceof FacetType) {
            return $value;
        }

        return blank($value) ? null : FacetType::from((int) $value);
    }
}
