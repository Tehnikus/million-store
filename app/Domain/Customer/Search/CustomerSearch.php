<?php

namespace App\Domain\Customer\Search;

use Illuminate\Database\Eloquent\Builder;

/**
 * FULLTEXT INDEX Customer search
 * The index itself is built by DB (see create_customer_table migration)
 * Index building is moved to DB because this fixes problem when Customers are imported directly to DB
 * thus skipping PHP application part
 */
class CustomerSearch
{

    private const float SIMILARITY_THRESHOLD = 0.4;
    /**
     * Strict search
     * Break search string into separate words and search every word
     * @param Builder $query
     * @param string $search
     * @return Builder
     */
    public static function strictSearch(Builder $query, string $search): Builder
    {
        $words = preg_split('/\s+/u', trim($search), -1, PREG_SPLIT_NO_EMPTY);

        foreach ($words as $word) {
            $query->whereRaw('addresses_search_text ilike ?', ['%' . $word . '%']);
        }

        return $query;
    }

    /**
     * Loose fuzzy search
     * @param Builder $query
     * @param string $search
     * @return Builder
     */
    public static function looseSearch(Builder $query, string $search): Builder
    {
        $words = preg_split('/\s+/u', trim($search), -1, PREG_SPLIT_NO_EMPTY);

        foreach ($words as $word) {
            if (mb_strlen($word) < 3) {
                $query->whereRaw('addresses_search_text ilike ?', ['%' . $word . '%']);
                continue;
            }

            $query->whereRaw('? <% addresses_search_text', [$word]);

            // Test this when more customers
            // $query->whereRaw(
            //     'word_similarity(?, addresses_search_text) > ?',
            //     [$word, self::SIMILARITY_THRESHOLD]
            // );
        }


        return $query;
    }
}