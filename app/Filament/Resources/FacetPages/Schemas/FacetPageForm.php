<?php

namespace App\Filament\Resources\FacetPages\Schemas;

use App\Domain\Catalog\FacetType;
use App\Domain\Media\ImageType;
use App\Filament\Schemas\Tabs\{DescriptionTab, FaqTab, FooterTab, HowToTab, ImagesTab};
use App\Models\Catalog\{FacetPage, FacetPageIndex};
use App\Models\Global\Store;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
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
                            ->icon(Heroicon::OutlinedPencilSquare)
                            ->schema([
                                Section::make(__('admin.catalog.facet_pages.fields.filter_settings'))
                                    ->description(__('admin.catalog.facet_pages.helpers.filter_settings'))
                                    ->collapsible()
                                    ->collapsed(fn ($operation) => $operation !== 'create')
                                    ->schema([

                                        Toggle::make('is_active')
                                            ->label(__('admin.catalog.facet_pages.fields.is_active'))
                                            ->default(true),
        
                                        // Parent facet. Must be category or manufacturer type. Always required

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
                                                ->options(fn (Get $get) => self::toFacetType($get('root_facet_type_id'))?->choices($store->id) ?? [])
                                                ->native(false)
                                                ->live()
                                                ->disabled(fn (Get $get) => blank($get('root_facet_type_id')))
                                                ->afterStateUpdated(function (Set $set, Get $get, $component, $livewire, ?string $state) use ($store) {
                                                    $type = self::toFacetType($get('root_facet_type_id'));
                                                    $set('root_facet_group_id', filled($state) && $type ? self::groupIdFor($type, $store->id, (int) $state) : 0);

                                                    $facets    = self::currentFacetSet($get, $get('facetIndex') ?? []);
                                                    $excludeId = method_exists($livewire, 'getRecord') ? $livewire->getRecord()?->id : null;

                                                    self::checkCombinationDuplicate($facets, $component->getStatePath(), $livewire, $store, $excludeId);
                                                })
                                                ->required()
                                                ->columnSpan(1),
                                        ])
                                        ->label(__('admin.catalog.facet_pages.fields.root_facet'))
                                        ->aboveContent(__('admin.catalog.facet_pages.helpers.root_facet'))
                                        ->columns(2)
                                        ->columnSpanFull(),

                                        Hidden::make('root_facet_group_id')->default(0),
                                                
                                        // Other facets, only non-root 
                                        Repeater::make('facetIndex')
                                            ->relationship(
                                                name: 'facetIndex',
                                                modifyQueryUsing: fn(Builder $query) => $query->where('is_root', false),
                                            )
                                            ->table([
                                                TableColumn::make(__('admin.catalog.facet_pages.fields.filter_type'))->markAsRequired()->width('50%'),
                                                TableColumn::make(__('admin.catalog.facet_pages.fields.filter_value'))->markAsRequired()->width('50%'),
                                            ])
                                            ->schema([
                                                Select::make('facet_type_id')
                                                    ->label(__('admin.catalog.facet_pages.fields.facet_type'))
                                                    ->live()
                                                    ->required()
                                                    ->options(
                                                        collect(FacetType::cases())
                                                            // Category always becomes the root by the priority rule, so it's never an additional facet
                                                            ->reject(fn(FacetType $t) => $t === FacetType::Category)
                                                            ->mapWithKeys(fn($t) => [$t->value => $t->getLabel()])
                                                    )
                                                    ->disableOptionWhen(function ($value, Get $get): bool {
                                                        $candidate = self::toFacetType($value);
                                                        $ownType = self::toFacetType($get('facet_type_id'));

                                                        if (!$candidate || $candidate === $ownType) {
                                                            return false;
                                                        }

                                                        // Root-capable types: a higher-priority type must be the root itself,
                                                        // the root's own type may repeat only for tags
                                                        if ($candidate->canBeRoot()) {
                                                            $root = self::toFacetType($get('../../root_facet_type_id'));

                                                            if (!$root || $candidate->rootPriority() < $root->rootPriority()) {
                                                                return true;
                                                            }

                                                            if ($candidate === $root && $candidate !== FacetType::Tag) {
                                                                return true;
                                                            }
                                                        }

                                                        if (!$candidate->isSingleton()) {
                                                            return false;
                                                        }

                                                        $usedElsewhere = collect($get('../') ?? [])
                                                            ->map(fn($item) => self::toFacetType($item['facet_type_id'] ?? null)?->value)
                                                            ->filter()
                                                            ->all();

                                                        return in_array($candidate->value, $usedElsewhere, true);
                                                    })
                                                    ->afterStateUpdated(function (Set $set) {
                                                        $set('facet_value_id', null);
                                                        $set('facet_group_id', 0);
                                                    }),
        
                                                Select::make('facet_value_id')
                                                    ->label(__('admin.catalog.facet_pages.fields.facet_value'))
                                                    ->searchable()
                                                    ->options(fn(Get $get) => self::toFacetType($get('facet_type_id'))?->choices($store->id) ?? [])
                                                    ->disableOptionWhen(function ($value, Get $get): bool {
                                                        $type = self::toFacetType($get('facet_type_id'));

                                                        if (!$type || (int) $value === (int) $get('facet_value_id')) {
                                                            return false;
                                                        }

                                                        // The root's own value can't repeat in additional facets (only possible for tags)
                                                        if (
                                                            $type === self::toFacetType($get('../../root_facet_type_id'))
                                                            && (int) $value === (int) $get('../../root_facet_value_id')
                                                        ) {
                                                            return true;
                                                        }

                                                        return in_array((int) $value, self::usedValueIds($get, $type), true);
                                                    })
                                                    ->native(false)
                                                    ->live()
                                                    ->disabled(fn (Get $get) => blank($get('facet_type_id'))) // Disable if facet_type_id is not selected
                                                    ->afterStateUpdated(function (Set $set, Get $get, $component, $livewire, ?string $state) use ($store) {
                                                        $type = self::toFacetType($get('facet_type_id'));
                                                        $set('facet_group_id', filled($state) && $type ? self::groupIdFor($type, $store->id, (int) $state) : 0);
    
                                                        $facets    = self::currentFacetSet($get, $get('../') ?? []);
                                                        $excludeId = method_exists($livewire, 'getRecord') ? $livewire->getRecord()?->id : null;
    
                                                        self::checkCombinationDuplicate($facets, $component->getStatePath(), $livewire, $store, $excludeId);
                                                    })
                                                    ->required()
                                                    ->validationMessages([
                                                        'required' => __('admin.catalog.facet_pages.errors.facet_value_required'),
                                                    ]),
        
                                                    Hidden::make('facet_group_id')->default(0),
                                            ])
                                            ->rules([
                                                function (?Model $record, Get $get) use ($store) {
                                                    return function (string $attribute, $value, \Closure $fail) use ($record, $store, $get) {
                                                        $root = self::toFacetType($get('root_facet_type_id'));
                                                        $rootValue = (int) $get('root_facet_value_id');

                                                        if ($root) {
                                                            foreach ($value ?? [] as $item) {
                                                                $type = self::toFacetType($item['facet_type_id'] ?? null);

                                                                if (!$type?->canBeRoot()) {
                                                                    continue;
                                                                }

                                                                // Priority rule: category => manufacturer => tag; only tags may repeat the root's type
                                                                if (
                                                                    $type->rootPriority() < $root->rootPriority()
                                                                    || ($type === $root && $type !== FacetType::Tag)
                                                                ) {
                                                                    $fail(__('admin.catalog.facet_pages.errors.root_priority'));
                                                                    return;
                                                                }

                                                                if ($type === $root && (int) ($item['facet_value_id'] ?? 0) === $rootValue) {
                                                                    $fail(__('admin.catalog.facet_pages.errors.duplicate_facet'));
                                                                    return;
                                                                }
                                                            }
                                                        }

                                                        $facets = self::currentFacetSet($get, $value ?? []);

                                                        if (self::queryFilterPageByFacets($store->id, $facets, excludePageId: $record?->id)) {
                                                            $fail(__('admin.catalog.facet_pages.errors.duplicate_combination'));
                                                        }
                                                    };
                                                },
                                            ])
                                            ->minItems(1)
                                            ->reorderable(false)
                                            ->label(__('admin.catalog.facet_pages.fields.additional_facets'))
                                            ->aboveContent(__('admin.catalog.facet_pages.helpers.additional_facets'))
                                            ->addActionLabel(__('admin.catalog.facet_pages.buttons.add_facet'))
                                            ->addActionAlignment('end')
                                            ->compact(),
                                    ]),


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
                        ImagesTab::make($store, $languages, ImageType::FacetPage)
                    ])
                    ->columnSpanFull()
                    ->contained(false),
            ]);
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
    private static function groupIdFor(FacetType $type, int $storeId, int $valueId): int
    {
        $modelClass = $type->modelClass();
        $column     = $type->groupIdColumn();

        if (!$modelClass || !$column) {
            return 0;
        }

        return $modelClass::where('store_id', $storeId)->find($valueId)?->{$column} ?? 0;
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
