<?php

namespace App\Domain\Catalog\Search;

use App\Models\Catalog\Product;
use App\Models\Global\Language;
use App\Models\Global\StoreLanguage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Product search over product_search_index (tsvector, built by SearchIndexer).
 *
 * How a query is matched:
 *  - the search string is split into words; every word becomes a prefix term ("lapt:*"), words are ANDed,
 *    so "dell lap" finds "Dell XPS 14 Laptop". Matching is by word start, NOT by substring:
 *    "metr" will not find "tonometr" (the old ilike '%metr%' did).
 *  - each store language is searched with ITS OWN text search config, because the stemming of the query
 *    must match the stemming of the indexed text. Languages sharing a config are grouped into one condition.
 *
 * Index usage: the config must be a constant in the SQL, never the ts_config column of the row
 * (websearch/to_tsquery(ts_config, ...) would depend on the row and the GIN index could not be used).
 * That is why the query is built from one (language_id IN (...) AND search_vector @@ ...) branch per config.
 *
 * Returns a Product query so callers can keep chaining ->pluck('id'), ->limit() and so on.
 */
class ProductSearch
{
    /** Hard limit of words in one search, to keep the tsquery small */
    private const MAX_TERMS = 8;

    /**
     * @param string   $search   user input
     * @param int|null $storeId  limit to products linked to this store (and search only its active languages)
     */
    public static function query(string $search = '', ?int $storeId = null): Builder
    {
        $query = Product::query()
            ->when(
                $storeId !== null,
                fn (Builder $query) => $query->whereHas(
                    'descriptions',
                    fn (Builder $q) => $q->where('store_id', $storeId),
                ),
            );

        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        $tsQuery  = static::toPrefixQuery($search);
        $branches = static::languageBranches($storeId);

        // Nothing searchable in the input (only punctuation) or the store has no active languages
        if ($tsQuery === '' || $branches === []) {
            $query->whereRaw('1 = 0');

            return $query;
        }

        // Not "return $query->whereIn(...)": Eloquent\Builder forwards whereIn() to the base query builder through
        // __call() and returns itself, which is correct at runtime, but static analysis infers the base builder type.
        // The closure of whereIn() receives a BASE query builder (not an Eloquent one) for the same reason.
        $query->whereIn('products.id', function (QueryBuilder $sub) use ($storeId, $tsQuery, $branches) {
            $sub->select('product_id')
                ->from('product_search_index')
                ->when($storeId !== null, fn ($q) => $q->where('store_id', $storeId))
                ->where(function ($q) use ($tsQuery, $branches) {
                    foreach ($branches as $config => $languageIds) {
                        $q->orWhere(function ($branch) use ($config, $languageIds, $tsQuery) {
                            $branch->whereIn('language_id', $languageIds)
                                ->whereRaw('search_vector @@ to_tsquery(?::regconfig, ?)', [$config, $tsQuery]);
                        });
                    }
                });
        });

        return $query;
    }

    /**
     * "dell lap" -> "dell:* & lap:*"
     *
     * Only letters and digits survive, so user input can never inject tsquery syntax (& | ! ( ) ' : *).
     * Words that are stop words in the language config (e.g. "s" in english) are dropped by Postgres itself.
     */
    private static function toPrefixQuery(string $search): string
    {
        $terms = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($search), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $terms = array_slice(array_unique($terms), 0, self::MAX_TERMS);

        return implode(' & ', array_map(fn (string $term) => $term . ':*', $terms));
    }

    /**
     * Languages to search, grouped by the text search config that actually exists in Postgres:
     * ['russian' => [3], 'simple' => [2], 'english' => [1]]
     *
     * With a store: languages active in that store (and globally), same rule as SearchIndexer.
     * Without a store: every active language.
     *
     * @return array<string, int[]>
     */
    private static function languageBranches(?int $storeId): array
    {
        $languages = Language::query()
            ->where('is_active', true)
            ->when(
                $storeId !== null,
                fn ($q) => $q->whereIn('id', StoreLanguage::query()
                    ->where('store_id', $storeId)
                    ->where('is_active', true)
                    ->select('language_id'))
            )
            ->get(['id', 'ts_config']);

        $resolver = app(TsConfigResolver::class);
        $branches = [];

        foreach ($languages as $language) {
            $branches[$resolver->resolve($language->ts_config)][] = $language->id;
        }

        return $branches;
    }
}