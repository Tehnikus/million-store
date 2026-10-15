<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\FacetType;
use App\Domain\Catalog\Search\SearchIndexer;
use App\Domain\Catalog\Search\SearchIndexObserver;
use App\Models\Catalog\Attribute;
use App\Models\Catalog\AttributeValue;
use App\Models\Catalog\Category;
use App\Models\Catalog\FacetIndex;
use App\Models\Catalog\Manufacturer;
use App\Models\Catalog\Option;
use App\Models\Catalog\OptionValue;
use App\Models\Catalog\Tag;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;

/**
 * Everything that happens when a catalog entity is deleted. Single place, read top to bottom.
 *
 * Entry point is BatchesCatalogDeletion::delete() -> batch(): the outermost delete opens a transaction and a batch,
 * nested deletes (values of a deleted group) run inside it.
 *
 *  deleting  (entity row and its relations still exist)
 *    Option / Attribute         -> delete values one by one (each runs this observer too)
 *    facet entities             -> collect their products from facet_index into the batch
 *    Category / Manufacturer    -> move children one level up, collect products that had it as primary
 *
 *  [row deleted; FK sets primary_category_id / primary_manufacturer_id = NULL]
 *
 *  deleted   (entity itself only, cheap set-based queries)
 *    facet entities             -> facet_index rows, facet pages (deactivated, non-root rows removed)
 *    Category / Manufacturer    -> primary moves to the parent (attached there), root category -> product inactive
 *    every entity               -> remembered for the product cleanup below
 *
 *  flush()   (end of the outermost delete, once per batch; everything that touches PRODUCTS)
 *    option values              -> combinations, their prices, is_available, orphan option rows in facet_index
 *    values / groups            -> per-product overrides (options_description, attributes_description)
 *    every entity               -> search_excluded_refs
 *    collected products         -> search index, each product once
 *
 * Batch state lives in hidden Context: observer instances live as long as the Octane worker, so no state here.
 * Other listeners: slugs (HasSlugs, "deleting"), images (HasProcessedImages, "deleted") run on their own.
 * Mass deletes through the query builder bypass models and all of this.
 */
class DeletionObserver
{
    private const ACTIVE = 'catalog_deletion.active';
    private const BATCH = 'catalog_deletion.batch';

    /**
     * Entities referenced by facet_index / facet_page_index (polymorphic via facet_type_id, no real FK)
     *
     * @return array<class-string<Model>, FacetType>
     */
    public static function facetTypes(): array
    {
        return [
            Category::class => FacetType::Category,
            Manufacturer::class => FacetType::Manufacturer,
            Tag::class => FacetType::Tag,
            AttributeValue::class => FacetType::AttributeValue,
            OptionValue::class => FacetType::OptionValue,
        ];
    }

    /**
     * Tree entities that can be a product's primary one: column in product_descriptions
     */
    private const PRIMARY_COLUMNS = [
        Category::class => 'primary_category_id',
        Manufacturer::class => 'primary_manufacturer_id',
    ];

    /**
     * Call once from AppServiceProvider::boot(). Order against SearchIndexObserver does not matter:
     * search rebuild on delete is done here, SearchIndexObserver only handles renames.
     */
    public static function register(): void
    {
        foreach ([...array_keys(static::facetTypes()), Option::class, Attribute::class] as $modelClass) {
            $modelClass::observe(static::class);
        }
    }

    // ------------------------------------------------------------------------------------------
    // Batch boundary
    // ------------------------------------------------------------------------------------------

    /**
     * Wraps Model::delete(), see BatchesCatalogDeletion.
     * Outermost call: transaction + batch, flush() at the end. Nested call: just runs inside them.
     */
    public static function batch(Closure $delete): mixed
    {
        if (Context::hasHidden(self::ACTIVE)) {
            return $delete();
        }

        Context::addHidden(self::ACTIVE, true);

        try {
            return DB::transaction(function () use ($delete) {
                $result = $delete();
                static::flush();

                return $result;
            });
        } finally {
            Context::forgetHidden(self::ACTIVE);
            Context::forgetHidden(self::BATCH);
        }
    }

    // ------------------------------------------------------------------------------------------
    // Model events
    // ------------------------------------------------------------------------------------------

    public function deleting(Model $model): void
    {
        $storeId = (int) $model->store_id;

        // Groups: a DB cascade would skip model events, so values go one by one, each feeding this batch
        if ($model instanceof Option || $model instanceof Attribute) {
            $model->values()->get()->each->delete();
            return;
        }

        $type = static::facetTypes()[$model::class] ?? null;

        if ($type === null) {
            return;
        }

        // Collected now: facet_index rows are removed in "deleted". Includes products where the entity
        // is excluded from search, which a search_included_refs lookup would miss
        static::remember($storeId, 'products', FacetIndex::query()
            ->where('store_id', $storeId)
            ->where('facet_type_id', $type->value)
            ->where('facet_value_id', $model->getKey())
            ->pluck('product_id')
            ->all());

        $primaryColumn = self::PRIMARY_COLUMNS[$model::class] ?? null;

        if ($primaryColumn === null) {
            return;
        }

        // Children move one level up through Eloquent, so their "updated" listener fixes facet_group_id.
        // get() first: each() pages by offset and would skip rows that leave the where after update
        $model->children()->get()->each(
            fn(Model $child) => $child->update(['parent_id' => $model->parent_id])
        );

        // The FK nulls primary_*_id when the row is deleted: remember who had it
        static::remember($storeId, static::primaryKey($model), DB::table('product_descriptions')
            ->where('store_id', $storeId)
            ->where($primaryColumn, $model->getKey())
            ->pluck('product_id')
            ->all());
    }

    public function deleted(Model $model): void
    {
        $storeId = (int) $model->store_id;
        $id = (int) $model->getKey();

        // Any entity may be listed in products' manual search exclusions (key = SearchRefType value)
        static::remember($storeId, 'excluded.' . SearchIndexObserver::map()[$model::class]->value, [$id]);

        // Product data cleaned in flush()
        match (true) {
            $model instanceof OptionValue => static::remember($storeId, "option_values.{$model->option_id}", [$id]),
            $model instanceof AttributeValue => static::remember($storeId, 'attribute_values', [$id]),
            $model instanceof Option => static::remember($storeId, 'option_groups', [$id]),
            $model instanceof Attribute => static::remember($storeId, 'attribute_groups', [$id]),
            default => null,
        };

        $type = static::facetTypes()[$model::class] ?? null;

        if ($type !== null) {
            FacetIndex::query()
                ->where('store_id', $storeId)
                ->where('facet_type_id', $type->value)
                ->where('facet_value_id', $id)
                ->delete();

            $this->deactivateFacetPages($type, $id, $storeId);
        }

        if (isset(self::PRIMARY_COLUMNS[$model::class])) {
            $this->movePrimary($model, $storeId);
        }

        // Safety net: deleted without BatchesCatalogDeletion (no batch around it) -> clean products right away
        if (!Context::hasHidden(self::ACTIVE)) {
            static::flush();
        }
    }

    // ------------------------------------------------------------------------------------------
    // Entity steps (run in "deleted")
    // ------------------------------------------------------------------------------------------

    /**
     * Facet pages (filter landing pages) built on a deleted facet entity:
     *  - every such page is deactivated: gone from the storefront, the admin sees it needs attention;
     *  - non-root rows pointing to the entity are removed, the rest of the combination stays;
     *  - root rows are kept: a page cannot exist without a root, the admin picks a new one or deletes the page.
     * The shortened combination may now equal another page's: caught by the facet page form's duplicate check
     * when the admin re-activates the page.
     */
    private function deactivateFacetPages(FacetType $type, int $entityId, int $storeId): void
    {
        DB::update(
            'UPDATE facet_pages SET is_active = false, updated_at = NOW()
             WHERE store_id = ? AND id IN (
                 SELECT facet_page_id FROM facet_page_index
                 WHERE store_id = ? AND facet_type_id = ? AND facet_value_id = ?
             )',
            [$storeId, $storeId, $type->value, $entityId],
        );

        DB::delete(
            'DELETE FROM facet_page_index
             WHERE store_id = ? AND facet_type_id = ? AND facet_value_id = ? AND is_root = false',
            [$storeId, $type->value, $entityId],
        );

        // TODO: Rebuild sitemap here
    }

    /**
     * Products that had the deleted category / manufacturer as primary (FK already set it to NULL):
     *  - has a parent: parent becomes primary and is attached (primary is always attached, see CategoryProductsRelationManager);
     *  - root category: product URL is built from its primary category, nowhere to move -> product inactive;
     *  - root manufacturer: product simply has no manufacturer, stays on sale.
     * Search for these products is rebuilt in flush(): they were collected as attached products in "deleting".
     */
    private function movePrimary(Model $model, int $storeId): void
    {
        $column = self::PRIMARY_COLUMNS[$model::class];
        $productIds = Arr::get(Context::getHidden(self::BATCH) ?? [], "{$storeId}." . static::primaryKey($model), []);

        if ($productIds === []) {
            return;
        }

        $parent = $model->parent_id ? $model::find($model->parent_id) : null;

        if ($parent !== null) {
            $parent->products()->syncWithoutDetaching(array_fill_keys($productIds, [
                'store_id' => $storeId,
                'facet_group_id' => $parent->parent_id ?? 0,
            ]));

            DB::table('product_descriptions')
                ->where('store_id', $storeId)
                ->whereIn('product_id', $productIds)
                ->whereNull($column)
                ->update([$column => $parent->getKey(), 'updated_at' => now()]);

            // TODO: Product URLs contain primary category: add redirects from old URLs here
            return;
        }

        if ($model instanceof Category) {
            DB::table('product_descriptions')
                ->where('store_id', $storeId)
                ->whereIn('product_id', $productIds)
                ->whereNull($column)
                ->update(['is_active' => false, 'updated_at' => now()]);
        }
    }

    // ------------------------------------------------------------------------------------------
    // Product steps (run once per batch)
    // ------------------------------------------------------------------------------------------

    /**
     * Batch layout, per store:
     *   products          => [product ids]                 search rebuild
     *   option_values     => [option_id => [value ids]]    combinations, overrides
     *   option_groups     => [option ids]                  overrides
     *   attribute_values  => [value ids]                   overrides
     *   attribute_groups  => [attribute ids]               overrides
     *   excluded          => [ref type => [ids]]           search_excluded_refs
     *   primary           => [Class => [id => [product ids]]] used by movePrimary()
     */
    public static function flush(): void
    {
        $batch = Context::getHidden(self::BATCH) ?? [];
        Context::forgetHidden(self::BATCH);

        foreach ($batch as $storeId => $work) {
            $storeId = (int) $storeId;
            $productIds = $work['products'] ?? [];

            if (!empty($work['option_values'])) {
                $productIds = [...$productIds, ...static::purgeCombinations($work['option_values'], $storeId)];
            }

            static::purgeOverrides(
                'options_description',
                'option_id',
                'option_value_id',
                $storeId,
                $work['option_groups'] ?? [],
                Arr::flatten($work['option_values'] ?? [])
            );

            static::purgeOverrides(
                'attributes_description',
                'attribute_id',
                'attribute_value_id',
                $storeId,
                $work['attribute_groups'] ?? [],
                $work['attribute_values'] ?? []
            );

            foreach ($work['excluded'] ?? [] as $refType => $ids) {
                static::purgeExcludedRefs((string) $refType, $ids, $storeId);
            }

            // Last: the indexer reads facet_index, overrides and exclusions, all clean by now
            if ($productIds !== []) {
                app(SearchIndexer::class)->rebuildProducts($productIds, $storeId);
            }

            // TODO: Rebuild sitemap here
        }
    }

    /**
     * Delete every combination containing any deleted value, prices of those combinations,
     * flag products left without combinations, drop option rows that lived only in the deleted combinations.
     *
     * @param array<int, int[]> $byOption option_id => deleted value ids
     * @return int[] products whose combinations changed
     */
    private static function purgeCombinations(array $byOption, int $storeId): array
    {
        // One jsonpath per option group, e.g. $."4" ? (@ == 31 || @ == "31"); ids are cast to int, safe to inline
        $paths = [];
        foreach ($byOption as $optionId => $valueIds) {
            $paths[] = '$."' . (int) $optionId . '" ? (' . static::jsonPathIn($valueIds) . ')';
        }

        $where = fn(string $column) => implode(' OR ', array_map(
            fn(string $path) => "jsonb_path_exists({$column}, '{$path}')",
            $paths,
        ));

        $deleted = DB::select(
            "DELETE FROM product_options WHERE store_id = ? AND ({$where('option_signature')}) RETURNING product_id",
            [$storeId],
            false, // write connection: this is a DELETE, even though it returns rows
        );

        // Prices have no store_id of their own, the store comes from the tier
        DB::delete(
            "DELETE FROM product_prices AS pp
             USING product_price_tiers AS t
             WHERE pp.product_price_tier_id = t.id AND t.store_id = ? AND ({$where('pp.option_signature')})",
            [$storeId],
        );

        $productIds = array_values(array_unique(array_map(fn($row) => (int) $row->product_id, $deleted)));

        if ($productIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($productIds), '?'));

        // Lost ALL combinations -> flag the product, so the admin can find it and set new prices
        DB::update(
            "UPDATE product_descriptions AS pd SET is_available = false, updated_at = NOW()
             WHERE pd.store_id = ? AND pd.product_id IN ({$placeholders})
               AND NOT EXISTS (
                   SELECT 1 FROM product_options AS po
                   WHERE po.product_id = pd.product_id AND po.store_id = pd.store_id
               )",
            [$storeId, ...$productIds],
        );

        // Values that lived only in the deleted combinations ("XL" of "Red + XL") leave facet_index too.
        // Assumes option rows in facet_index are built from combinations only
        DB::delete(
            "DELETE FROM facet_index AS fi
             WHERE fi.store_id = ? AND fi.facet_type_id = ? AND fi.product_id IN ({$placeholders})
               AND NOT EXISTS (
                   SELECT 1 FROM product_options AS po
                   WHERE po.product_id = fi.product_id AND po.store_id = fi.store_id
                     AND (po.option_signature @> jsonb_build_object(fi.facet_group_id::text, fi.facet_value_id)
                       OR po.option_signature @> jsonb_build_object(fi.facet_group_id::text, fi.facet_value_id::text))
               )",
            [$storeId, FacetType::OptionValue->value, ...$productIds],
        );

        return $productIds;
    }

    /**
     * Remove deleted values and whole groups from a per-product override column in one pass:
     *   [{option_id, name, description: [{option_value_id, name, description}, ...]}, ...]
     *
     * @param 'options_description'|'attributes_description' $column  never user input
     * @param int[] $groupIds whole groups deleted
     * @param int[] $valueIds single values deleted
     */
    private static function purgeOverrides(string $column, string $groupKey, string $valueKey, int $storeId, array $groupIds, array $valueIds): void
    {
        $paths = [];
        if ($valueIds !== []) {
            $paths[] = '$.**.' . $valueKey . ' ? (' . static::jsonPathIn($valueIds) . ')';
        }
        if ($groupIds !== []) {
            $paths[] = '$.**.' . $groupKey . ' ? (' . static::jsonPathIn($groupIds) . ')';
        }

        if ($paths === []) {
            return;
        }

        // .** = any depth: matches both list and uuid-keyed shapes
        $where = implode(' OR ', array_map(fn(string $path) => "jsonb_path_exists({$column}, '{$path}')", $paths));

        $rows = DB::select(
            "SELECT id, {$column} AS data FROM product_descriptions WHERE store_id = ? AND ({$where})",
            [$storeId],
        );

        $groupIds = array_map('intval', $groupIds);
        $valueIds = array_map('intval', $valueIds);

        foreach ($rows as $row) {
            $groups = json_decode((string) $row->data, true);

            if (!is_array($groups)) {
                continue;
            }

            $cleaned = static::removeFromGroups($groups, $groupKey, $valueKey, $groupIds, $valueIds);

            if ($cleaned === $groups) {
                continue;
            }

            // Keep the empty shape the column had: [] for a list, {} for a uuid-keyed object
            $json = $cleaned === [] ? (array_is_list($groups) ? '[]' : '{}') : json_encode($cleaned);

            DB::update(
                "UPDATE product_descriptions SET {$column} = ?::jsonb, updated_at = NOW() WHERE id = ?",
                [$json, $row->id],
            );
        }
    }

    /**
     * Pure array part of purgeOverrides(), public static so it can be tested without a database.
     * A group left without values carries no meaning and is removed too. Shapes (list / keyed) are preserved.
     *
     * @param int[] $groupIds
     * @param int[] $valueIds
     */
    public static function removeFromGroups(array $groups, string $groupKey, string $valueKey, array $groupIds, array $valueIds): array
    {
        $wasList = array_is_list($groups);
        $result = [];

        foreach ($groups as $key => $group) {
            if (!is_array($group)) {
                $result[$key] = $group;
                continue;
            }

            // Whole group deleted
            if (in_array((int) ($group[$groupKey] ?? 0), $groupIds, true)) {
                continue;
            }

            $values = (array) ($group['description'] ?? []);
            $kept = array_filter(
                $values,
                fn($value) => !is_array($value) || !in_array((int) ($value[$valueKey] ?? 0), $valueIds, true),
            );

            // Untouched group stays as it was, even if it had no values before
            if (count($kept) === count($values)) {
                $result[$key] = $group;
                continue;
            }

            if ($kept === []) {
                continue;
            }

            $group['description'] = array_is_list($values) ? array_values($kept) : $kept;
            $result[$key] = $group;
        }

        return $wasList ? array_values($result) : $result;
    }

    /**
     * search_excluded_refs = {"option_value": [31, "32"], ...}: drop deleted ids of one type, numbers and strings alike.
     *
     * @param string $refType SearchRefType value, never user input
     * @param int[] $ids
     */
    private static function purgeExcludedRefs(string $refType, array $ids, int $storeId): void
    {
        $match = static::jsonPathIn($ids);
        $path = '$."' . $refType . '"[*]';

        DB::update(
            "UPDATE product_descriptions
             SET search_excluded_refs = jsonb_set(
                     search_excluded_refs,
                     ARRAY[?::text],
                     jsonb_path_query_array(search_excluded_refs, '{$path} ? (!({$match}))')
                 ),
                 updated_at = NOW()
             WHERE store_id = ? AND jsonb_path_exists(search_excluded_refs, '{$path} ? ({$match})')",
            [$refType, $storeId],
        );
    }

    // ------------------------------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------------------------------

    /**
     * Add ids to a batch list (deduplicated). $path is relative to the store, dot notation.
     *
     * @param int[] $ids
     */
    private static function remember(int $storeId, string $path, array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $batch = Context::getHidden(self::BATCH) ?? [];
        $key = "{$storeId}.{$path}";

        Arr::set($batch, $key, array_values(array_unique([...Arr::get($batch, $key, []), ...array_map('intval', $ids)])));

        Context::addHidden(self::BATCH, $batch);
    }

    /**
     * Batch path for products that had this category / manufacturer as primary.
     * Class name in the path: a category and a manufacturer may share the same id.
     */
    private static function primaryKey(Model $model): string
    {
        return 'primary.' . class_basename($model) . '.' . $model->getKey();
    }

    /**
     * jsonpath predicate "@ equals any of these ids", as number or as string (form state stores both).
     * Ids are cast to int, so inlining them into the SQL string is safe.
     *
     * @param int[] $ids
     */
    private static function jsonPathIn(array $ids): string
    {
        return implode(' || ', array_map(
            fn($id) => '@ == ' . (int) $id . ' || @ == "' . (int) $id . '"',
            $ids,
        ));
    }
}