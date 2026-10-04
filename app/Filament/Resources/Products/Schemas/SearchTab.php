<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Domain\Catalog\FacetType;
use App\Domain\Catalog\Search\SearchIndexer;
use App\Domain\Catalog\Search\SearchRefType;
use App\Models\Catalog\{Attribute, AttributeValue, Category, Manufacturer, Option, OptionValue, Product, ProductDescription, Tag};
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Support\Facades\DB;

/**
 * Product form tab: manual search settings.
 *
 *  - description.search_custom_terms.{locale}  free text that makes the product findable ("Best blood pressure monitor")
 *  - description.search_excluded_refs          entities the user removed from this product's search index
 *
 * The excluded-entities select is built from what the index was built from at the LAST SAVE
 * (product_search_index.search_included_refs) plus the current exclusions, not from unsaved form state.
 * Reason: category/manufacturer/option repeaters on other tabs re-render partially, so options computed
 * from live form state would silently go stale. The list refreshes after saving the product.
 *
 * DB shape of search_excluded_refs: {"category": [5], "option_value": [31]}
 * Form state shape (Select multiple needs a flat list): ["category:5", "option_value:31"]
 */
class SearchTab
{
    public static function make($store, $languages): Tab
    {
        return Tab::make('search')
            ->schema([

                Section::make(__('admin.catalog.products.tabs.search.labels.custom_terms'))
                    ->description(__('admin.catalog.products.tabs.search.helpers.custom_terms'))
                    ->schema([
                        FusedGroup::make(
                            collect($languages)->map(
                                fn ($language) =>
                                TextInput::make("description.search_custom_terms.{$language->locale}")
                                    ->prefix($language->locale)
                                    ->maxLength(500)
                                    ->hiddenLabel()
                                    ->placeholder(__('admin.catalog.products.tabs.search.placeholders.custom_terms'))
                            )->all()
                        )
                            ->columnSpanFull()
                            ->label(__('admin.catalog.products.tabs.search.labels.custom_terms')),
                    ]),

                Section::make(__('admin.catalog.products.tabs.search.labels.excluded_refs'))
                    ->description(__('admin.catalog.products.tabs.search.helpers.excluded_refs'))
                    ->schema([
                        Select::make('search_excluded_refs')
                            ->statePath('description.search_excluded_refs')
                            ->multiple()
                            ->searchable()
                            ->options(fn (?Product $record) => static::candidates($record, $store))
                            ->placeholder(__('admin.catalog.products.tabs.search.placeholders.excluded_refs'))
                            ->hiddenLabel()
                            // DB -> form: {"category": [5]} -> ["category:5"]
                            ->formatStateUsing(fn ($state) => static::flattenRefs((array) $state))
                            // Form -> DB: ["category:5"] -> {"category": [5]}
                            ->dehydrateStateUsing(fn ($state) => static::groupRefs((array) $state)),
                    ]),
            ]);
    }

    /**
     * Options of the "excluded" select: every entity the index currently contains plus every current exclusion
     * (so an excluded entity can be brought back, even if it is no longer linked to the product).
     *
     * @return array<string, string>  "type:id" => "Type: name"
     */
    protected static function candidates(?Product $record, $store): array
    {
        if ($record === null) {
            return [];
        }

        $description = ProductDescription::query()
            ->where('product_id', $record->id)
            ->where('store_id', $store->id)
            ->first();

        if ($description === null) {
            return [];
        }

        // Entities the index was built from at the last save
        $refs = static::indexedRefs($record->id, $store->id);

        // ...plus current exclusions
        foreach ($description->search_excluded_refs ?? [] as $type => $ids) {
            $refs[$type] = array_values(array_unique([...($refs[$type] ?? []), ...array_map('intval', (array) $ids)]));
        }

        $locale    = app()->getLocale();
        $overrides = app(SearchIndexer::class)->nameOverrides($description);
        $items     = [];

        foreach (SearchRefType::cases() as $type) {
            $ids = $refs[$type->value] ?? [];

            if ($ids === []) {
                continue;
            }

            // Global reference-book names are the fallback when the product has no override
            $globalNames = static::modelClass($type)::query()
                ->whereIn('id', $ids)
                ->get()
                ->mapWithKeys(fn ($model) => [$model->id => $model->name]);

            $typeItems = [];

            foreach ($ids as $id) {
                $override = trim((string) ($overrides[$type->value][$id][$locale] ?? ''));
                $name     = $override !== '' ? $override : ($globalNames[$id] ?? "#{$id}");

                $typeItems["{$type->value}:{$id}"] = static::typeLabel($type) . ': ' . $name;
            }

            asort($typeItems);
            $items += $typeItems;
        }

        return $items;
    }

    /**
     * Union of search_included_refs over all language rows of this product in this store.
     *
     * @return array<string, int[]>
     */
    protected static function indexedRefs(int $productId, int $storeId): array
    {
        $merged = [];

        DB::table('product_search_index')
            ->where('product_id', $productId)
            ->where('store_id', $storeId)
            ->pluck('search_included_refs')
            ->each(function ($json) use (&$merged) {
                foreach (json_decode((string) $json, true) ?? [] as $type => $ids) {
                    $merged[$type] = array_values(array_unique([...($merged[$type] ?? []), ...array_map('intval', $ids)]));
                }
            });

        return $merged;
    }

    /**
     * @return class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected static function modelClass(SearchRefType $type): string
    {
        return match ($type) {
            SearchRefType::Category       => Category::class,
            SearchRefType::Manufacturer   => Manufacturer::class,
            SearchRefType::Tag            => Tag::class,
            SearchRefType::Attribute      => Attribute::class,
            SearchRefType::AttributeValue => AttributeValue::class,
            SearchRefType::Option         => Option::class,
            SearchRefType::OptionValue    => OptionValue::class,
        };
    }

    protected static function typeLabel(SearchRefType $type): string
    {
        return match ($type) {
            SearchRefType::Category       => FacetType::Category->getLabel(),
            SearchRefType::Manufacturer   => FacetType::Manufacturer->getLabel(),
            SearchRefType::Tag            => FacetType::Tag->getLabel(),
            SearchRefType::Attribute      => __('admin.catalog.products.tabs.search.types.attribute_group'),
            SearchRefType::AttributeValue => FacetType::AttributeValue->getLabel(),
            SearchRefType::Option         => __('admin.catalog.products.tabs.search.types.option_group'),
            SearchRefType::OptionValue    => FacetType::OptionValue->getLabel(),
        };
    }

    /**
     * {"category": [5], "option_value": [31]} -> ["category:5", "option_value:31"]
     */
    protected static function flattenRefs(array $refs): array
    {
        if (array_is_list($refs)) {
            return $refs; // already flat
        }

        $flat = [];

        foreach ($refs as $type => $ids) {
            foreach ((array) $ids as $id) {
                $flat[] = "{$type}:{$id}";
            }
        }

        return $flat;
    }

    /**
     * ["category:5", "option_value:31"] -> {"category": [5], "option_value": [31]}
     * Unknown types and non-numeric ids are dropped. An empty result is saved as an empty array.
     */
    protected static function groupRefs(array $flat): array
    {
        $refs = [];

        foreach ($flat as $value) {
            [$type, $id] = array_pad(explode(':', (string) $value, 2), 2, null);

            if (SearchRefType::tryFrom((string) $type) === null || ! ctype_digit((string) $id)) {
                continue;
            }

            $refs[$type][] = (int) $id;
        }

        return array_map(function (array $ids) {
            $ids = array_values(array_unique($ids));
            sort($ids);

            return $ids;
        }, $refs);
    }
}