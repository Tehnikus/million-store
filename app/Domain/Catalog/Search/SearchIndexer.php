<?php

namespace App\Domain\Catalog\Search;

use App\Domain\Catalog\FacetType;
use App\Models\Catalog\Attribute;
use App\Models\Catalog\AttributeValue;
use App\Models\Catalog\Category;
use App\Models\Catalog\FacetIndex;
use App\Models\Catalog\Manufacturer;
use App\Models\Catalog\Option;
use App\Models\Catalog\OptionValue;
use App\Models\Catalog\ProductDescription;
use App\Models\Catalog\Tag;
use App\Models\Global\Language;
use App\Models\Global\StoreLanguage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Builds product_search_index rows. The only place that knows how a search document is assembled.
 *
 * PHP assembles four weighted texts per (product, store, language); Postgres turns them into
 * a tsvector (generated column) - see the product_search_index migration.
 *
 *   text_a  product name
 *   text_b  categories, manufacturers, custom search terms
 *   text_c  attributes and options (group name followed by its values)
 *   text_d  tags
 *
 * Source of truth for what goes into the document:
 *   facet_index (product relations) - product_descriptions.search_excluded_refs (manual exclusions)
 * The result of that subtraction is saved to search_included_refs, which is what makes
 * "entity X changed -> which rows to rebuild" a single indexed lookup.
 *
 * Everything here is synchronous for now. If rebuilds ever move to queue jobs, only the callers change:
 * dispatch a job that calls rebuildProduct() / rebuildForEntity() instead of calling them directly.
 *
 * Do NOT bind as a singleton (Octane): the instance caches store languages and ts configs.
 */
class SearchIndexer
{
    /** @var array<int, Collection<int, Language>> active languages per store */
    private array $storeLanguages = [];

    /** @var array<string, string> requested ts_config name => name that actually exists in Postgres */
    private array $tsConfigs = [];

    // ------------------------------------------------------------------------------------------
    // Public API
    // ------------------------------------------------------------------------------------------

    /**
     * Rebuild every index row of one store. Use for the first fill and after big imports.
     */
    public function rebuildStore(int $storeId): void
    {
        DB::table('product_descriptions')
            ->where('store_id', $storeId)
            ->orderBy('product_id')
            ->pluck('product_id')
            ->chunk(200)
            ->each(fn (Collection $ids) => $ids->each(
                fn (int $productId) => $this->rebuildProduct($productId, $storeId)
            ));
    }

    /**
     * Rebuild index rows of ONE product in ONE store.
     *
     * @param int[]|null $languageIds  limit to these languages; null = all active languages of the store
     *                                 (a full rebuild also removes rows of languages that are no longer active)
     */
    public function rebuildProduct(int $productId, int $storeId, ?array $languageIds = null): void
    {
        $description = ProductDescription::query()
            ->where('product_id', $productId)
            ->where('store_id', $storeId)
            ->first();

        // Product is not sold in this store (anymore): nothing to index
        if ($description === null) {
            DB::table('product_search_index')
                ->where('product_id', $productId)
                ->where('store_id', $storeId)
                ->delete();
            return;
        }

        $activeLanguages = $this->storeLanguages($storeId);
        $languages       = $languageIds === null
            ? $activeLanguages
            : $activeLanguages->whereIn('id', $languageIds);

        // Entities are language-independent, so load them once and reuse for every language
        $entities = $this->collectEntities($productId, $storeId, $description->search_excluded_refs ?? []);
        $refs     = $this->buildRefs($entities);
        $overrides = $this->nameOverrides($description);

        DB::transaction(function () use ($description, $productId, $storeId, $languageIds, $activeLanguages, $languages, $entities, $refs, $overrides) {

            if ($languageIds === null) {
                DB::table('product_search_index')
                    ->where('product_id', $productId)
                    ->where('store_id', $storeId)
                    ->whereNotIn('language_id', $activeLanguages->pluck('id')->all())
                    ->delete();
            }

            foreach ($languages as $language) {
                $texts = $this->buildTexts($description, $entities, $overrides, $language->locale);

                // No name in this language = product is not translated there = not searchable there.
                // Without this a row with only category/tag words would find untranslated products.
                if ($texts['a'] === '') {
                    DB::table('product_search_index')
                        ->where('product_id', $productId)
                        ->where('store_id', $storeId)
                        ->where('language_id', $language->id)
                        ->delete();
                    continue;
                }

                $this->upsertRow($productId, $storeId, $language, $texts, $refs);
            }
        });
    }

    /**
     * Reverse lookup: an entity was renamed / deleted -> rebuild only the rows that contain it.
     * Rows are found through the GIN index on search_included_refs (containment operator @>).
     *
     * @param int[]|null $languageIds  limit to these languages, e.g. only the language whose name was edited
     * @return int number of (product, store) pairs rebuilt
     */
    public function rebuildForEntity(SearchRefType $type, int $entityId, ?array $languageIds = null): int
    {
        $rows = DB::table('product_search_index')
            ->whereRaw('search_included_refs @> ?::jsonb', [json_encode([$type->value => [$entityId]])])
            ->when($languageIds !== null, fn ($query) => $query->whereIn('language_id', $languageIds))
            ->get(['product_id', 'store_id', 'language_id']);

        // One rebuild per (product, store) covering all affected languages at once
        $groups = $rows->groupBy(fn ($row) => "{$row->product_id}:{$row->store_id}");

        foreach ($groups as $group) {
            $first = $group->first();
            $this->rebuildProduct($first->product_id, $first->store_id, $group->pluck('language_id')->all());
        }

        return $groups->count();
    }

    // ------------------------------------------------------------------------------------------
    // Collecting entities
    // ------------------------------------------------------------------------------------------

    /**
     * Load every entity that should be in the product's search document, minus manual exclusions.
     *
     * Exclusion rules:
     *  - excluding an attribute/option GROUP drops all of its values
     *  - excluding a single VALUE keeps the group name only if other values of that group remain
     *    (a lone "Screen" without a value is just noise)
     *
     * Only directly assigned categories are indexed, not their parents.
     *
     * @return array{categories: Collection, manufacturers: Collection, tags: Collection, attributes: Collection, options: Collection}
     */
    private function collectEntities(int $productId, int $storeId, array $excluded): array
    {
        // facet_index is the single place where product <-> entity relations live
        $facets = FacetIndex::query()
            ->where('product_id', $productId)
            ->where('store_id', $storeId)
            ->get(['facet_type_id', 'facet_group_id', 'facet_value_id'])
            ->groupBy(fn (FacetIndex $row) => $row->facet_type_id->value);

        return [
            'categories'    => $this->collectSimple($facets, FacetType::Category,     Category::class,     SearchRefType::Category,     $excluded),
            'manufacturers' => $this->collectSimple($facets, FacetType::Manufacturer, Manufacturer::class, SearchRefType::Manufacturer, $excluded),
            'tags'          => $this->collectSimple($facets, FacetType::Tag,          Tag::class,          SearchRefType::Tag,          $excluded),
            'attributes'    => $this->collectGrouped(
                $facets, FacetType::AttributeValue, Attribute::class, AttributeValue::class,
                SearchRefType::Attribute, SearchRefType::AttributeValue, $excluded
            ),
            'options'       => $this->collectGrouped(
                $facets, FacetType::OptionValue, Option::class, OptionValue::class,
                SearchRefType::Option, SearchRefType::OptionValue, $excluded
            ),
        ];
    }

    /**
     * Flat entities: categories, manufacturers, tags.
     *
     * @param class-string<Model> $modelClass
     */
    private function collectSimple(Collection $facets, FacetType $type, string $modelClass, SearchRefType $ref, array $excluded): Collection
    {
        $ids = $facets->get($type->value, collect())
            ->pluck('facet_value_id')
            ->map(fn ($id) => (int) $id)
            ->diff($this->excludedIds($excluded, $ref))
            ->values()
            ->all();

        if ($ids === []) {
            return collect();
        }

        return $modelClass::query()->whereIn('id', $ids)->orderBy('id')->get();
    }

    /**
     * Two-level entities: attribute -> values, option -> values.
     * facet_index keeps only the value id; the group id sits in facet_group_id.
     *
     * @param class-string<Model> $groupClass
     * @param class-string<Model> $valueClass
     * @return Collection<int, array{group: Model, values: Collection}>
     */
    private function collectGrouped(
        Collection $facets,
        FacetType $type,
        string $groupClass,
        string $valueClass,
        SearchRefType $groupRef,
        SearchRefType $valueRef,
        array $excluded
    ): Collection {
        $excludedGroups = $this->excludedIds($excluded, $groupRef);
        $excludedValues = $this->excludedIds($excluded, $valueRef);

        $rows = $facets->get($type->value, collect())->reject(
            fn ($row) => in_array((int) $row->facet_group_id, $excludedGroups, true)
                      || in_array((int) $row->facet_value_id, $excludedValues, true)
        );

        if ($rows->isEmpty()) {
            return collect();
        }

        $groups = $groupClass::query()->whereIn('id', $rows->pluck('facet_group_id')->unique()->all())->get()->keyBy('id');
        $values = $valueClass::query()->whereIn('id', $rows->pluck('facet_value_id')->all())->get()->keyBy('id');

        return $rows
            ->groupBy('facet_group_id')
            ->map(fn ($groupRows, $groupId) => [
                'group'  => $groups->get($groupId),
                'values' => $groupRows->map(fn ($row) => $values->get($row->facet_value_id))->filter()->values(),
            ])
            // Dangling facet rows (entity deleted without cleanup) and groups left without values are dropped
            ->filter(fn (array $item) => $item['group'] !== null && $item['values']->isNotEmpty())
            ->values();
    }

    /**
     * @return int[]
     */
    private function excludedIds(array $excluded, SearchRefType $ref): array
    {
        return array_map('intval', $excluded[$ref->value] ?? []);
    }

    // ------------------------------------------------------------------------------------------
    // Building refs and texts
    // ------------------------------------------------------------------------------------------

    /**
     * search_included_refs: ids of every entity that took part in the document, e.g.
     * {"category": [5], "attribute": [2], "attribute_value": [14], "tag": [9]}
     *
     * Language-independent on purpose: an entity without a translation in some language is still
     * listed, so adding that translation later finds the row and rebuilds it.
     *
     * @return array<string, int[]>
     */
    private function buildRefs(array $entities): array
    {
        $refs = [
            SearchRefType::Category->value       => $entities['categories']->pluck('id'),
            SearchRefType::Manufacturer->value   => $entities['manufacturers']->pluck('id'),
            SearchRefType::Tag->value            => $entities['tags']->pluck('id'),
            SearchRefType::Attribute->value      => $entities['attributes']->map(fn (array $item) => $item['group']->id),
            SearchRefType::AttributeValue->value => $entities['attributes']->flatMap(fn (array $item) => $item['values']->pluck('id')),
            SearchRefType::Option->value         => $entities['options']->map(fn (array $item) => $item['group']->id),
            SearchRefType::OptionValue->value    => $entities['options']->flatMap(fn (array $item) => $item['values']->pluck('id')),
        ];

        // Sorted + unique + no empty types: the same product always produces byte-identical jsonb
        return collect($refs)
            ->map(fn (Collection $ids) => $ids->map(fn ($id) => (int) $id)->unique()->sort()->values()->all())
            ->filter(fn (array $ids) => $ids !== [])
            ->all();
    }

    /**
     * @return array{a: string, b: string, c: string, d: string}
     */
    private function buildTexts(ProductDescription $description, array $entities, array $overrides, string $locale): array
    {
        // Name in this store and language; global_name is the fallback. Strict locale, no fallback locale:
        // an Ukrainian row must not be filled with Russian words.
        $name = $this->translated($description, 'name', $locale)
            ?: $this->translated($description->product, 'global_name', $locale);

        return [
            'a' => $this->joinWords([$name]),
            'b' => $this->joinWords([
                ...$this->names($entities['categories'], $locale),
                ...$this->names($entities['manufacturers'], $locale),
                $this->translated($description, 'search_custom_terms', $locale),
            ]),
            'c' => $this->joinWords([
                ...$this->groupWords($entities['attributes'], SearchRefType::Attribute, SearchRefType::AttributeValue, $overrides, $locale),
                ...$this->groupWords($entities['options'], SearchRefType::Option, SearchRefType::OptionValue, $overrides, $locale),
            ]),
            'd' => $this->joinWords($this->names($entities['tags'], $locale)),
        ];
    }

    /**
     * "Screen OLED IPS", "RAM 16GB 32GB": group name once, followed by its values.
     *
     * @return string[]
     */
    private function groupWords(Collection $groups, SearchRefType $groupRef, SearchRefType $valueRef, array $overrides, string $locale): array
    {
        $words = [];

        foreach ($groups as $item) {
            $words[] = $this->displayName($item['group'], $groupRef, $overrides, $locale);

            foreach ($item['values'] as $value) {
                $words[] = $this->displayName($value, $valueRef, $overrides, $locale);
            }
        }

        return $words;
    }

    /**
     * Per-product name overrides saved by the product form into product_descriptions:
     *   attributes_description: [{attribute_id, name: {locale: ...}, description: [{attribute_value_id, name: {locale: ...}}]}]
     *   options_description:    [{option_id,    name: {locale: ...}, description: [{option_value_id,    name: {locale: ...}}]}]
     * The storefront shows these names, so the search must index them too.
     *
     * Note the form copies the global name into the override when an attribute/option is picked, so an override
     * is a snapshot: renaming the global entity later does not change what this product shows (or indexes).
     *
     * Public because the product form reuses it to label the "excluded from search" tags with the same names.
     *
     * @return array<string, array<int, array<string, string>>>  e.g. ['attribute' => [2 => ['ru' => 'Дисплей']], 'option_value' => [...]]
     */
    public function nameOverrides(ProductDescription $description): array
    {
        $sources = [
            [$description->attributes_description, 'attribute_id', 'attribute_value_id', SearchRefType::Attribute, SearchRefType::AttributeValue],
            [$description->options_description,    'option_id',    'option_value_id',    SearchRefType::Option,    SearchRefType::OptionValue],
        ];

        $overrides = [];

        foreach ($sources as [$groups, $groupKey, $valueKey, $groupRef, $valueRef]) {
            foreach ($groups ?? [] as $group) {
                $overrides[$groupRef->value][(int) ($group[$groupKey] ?? 0)] = $group['name'] ?? [];

                foreach ($group['description'] ?? [] as $value) {
                    $overrides[$valueRef->value][(int) ($value[$valueKey] ?? 0)] = $value['name'] ?? [];
                }
            }
        }

        return $overrides;
    }

    /**
     * Name as the storefront shows it: product override in this locale, else the global reference book name.
     * An empty override falls back too (the form allows clearing it).
     */
    private function displayName(Model $model, SearchRefType $ref, array $overrides, string $locale): string
    {
        $override = trim((string) ($overrides[$ref->value][$model->getKey()][$locale] ?? ''));

        return $override !== '' ? $override : $this->translated($model, 'name', $locale);
    }

    /**
     * @return string[]
     */
    private function names(Collection $models, string $locale): array
    {
        return $models->map(fn (Model $model) => $this->translated($model, 'name', $locale))->all();
    }

    /**
     * Translation in exactly this locale (no fallback locale), always a trimmed string.
     */
    private function translated(Model $model, string $field, string $locale): string
    {
        return trim((string) $model->getTranslation($field, $locale, false));
    }

    private function joinWords(array $words): string
    {
        $text = implode(' ', array_filter($words, fn ($word) => $word !== ''));

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    // ------------------------------------------------------------------------------------------
    // Persistence
    // ------------------------------------------------------------------------------------------

    /**
     * Raw upsert instead of Eloquent: ts_config is a regconfig column and the table has a generated column.
     * search_vector is never written - Postgres recomputes it from ts_config and text_a..d.
     */
    private function upsertRow(int $productId, int $storeId, Language $language, array $texts, array $refs): void
    {
        $now = now();

        DB::statement(<<<'SQL'
            INSERT INTO product_search_index
                (product_id, store_id, language_id, ts_config, text_a, text_b, text_c, text_d, search_included_refs, created_at, updated_at)
            VALUES
                (?, ?, ?, ?::regconfig, ?, ?, ?, ?, ?::jsonb, ?, ?)
            ON CONFLICT (product_id, store_id, language_id) DO UPDATE SET
                ts_config            = EXCLUDED.ts_config,
                text_a               = EXCLUDED.text_a,
                text_b               = EXCLUDED.text_b,
                text_c               = EXCLUDED.text_c,
                text_d               = EXCLUDED.text_d,
                search_included_refs = EXCLUDED.search_included_refs,
                updated_at           = EXCLUDED.updated_at
        SQL, [
            $productId,
            $storeId,
            $language->id,
            $this->tsConfig($language),
            $texts['a'],
            $texts['b'],
            $texts['c'],
            $texts['d'],
            $refs === [] ? '{}' : json_encode($refs),   // json_encode([]) would give "[]", not an object
            $now,
            $now,
        ]);
    }

    /**
     * Languages the store is currently sold in: active in the store AND active globally.
     *
     * @return Collection<int, Language>
     */
    private function storeLanguages(int $storeId): Collection
    {
        return $this->storeLanguages[$storeId] ??= Language::query()
            ->where('is_active', true)
            ->whereIn('id', StoreLanguage::query()
                ->where('store_id', $storeId)
                ->where('is_active', true)
                ->select('language_id'))
            ->orderBy('id')
            ->get();
    }

    /**
     * languages.ts_config is a free-form string, but a missing dictionary makes the ::regconfig cast fail
     * and would break saving a product (inside a transaction it would also abort it).
     * Fall back to 'simple' (no stemming, still searchable) and log it.
     * Checked against the pg_ts_config catalog, limited to configs visible in the current search_path -
     * exactly the ones the ::regconfig cast can resolve. Schema-qualified names ('public.ukrainian')
     * are not supported here; install the dictionary into a schema on the search_path instead.
     */
    private function tsConfig(Language $language): string
    {
        $wanted = $language->ts_config ?: 'simple';

        if (! isset($this->tsConfigs[$wanted])) {
            $exists = (bool) DB::scalar(
                'SELECT EXISTS (SELECT 1 FROM pg_ts_config WHERE cfgname = ? AND pg_ts_config_is_visible(oid))',
                [$wanted]
            );

            if (! $exists) {
                Log::warning("Search: text search config '{$wanted}' of language #{$language->id} does not exist in Postgres, using 'simple'.");
            }

            $this->tsConfigs[$wanted] = $exists ? $wanted : 'simple';
        }

        return $this->tsConfigs[$wanted];
    }
}