<?php
namespace App\Domain\Catalog\Search;

class ProductSearch
{
    /**
     * Draft for tsvector FULLTEXT product search with morphology
     * Currently it's just simple search by global_name
     * In future I'll make separate product dictionary table with stored tsvector and GIN index.
     * 
     * For future reference:
     * Column with stored data
     * search_vector tsvector GENERATED ALWAYS AS (
     *   to_tsvector('english', coalesce(name, '') || ' ' || coalesce(description, ''))
     * ) STORED
     * 
     * Create GIN index
     * CREATE INDEX articles_search_idx ON articles USING GIN (search_vector);
     * 
     * Search over column
     * SELECT title FROM articles 
     * WHERE search_vector @@ to_tsquery('english', 'database & performance');
     * 
     * @param string $search
     * @param int $limit
     * @return \Illuminate\Support\Collection<int, \stdClass>
     */
    public static function search(string $search, int $limit = 20): \Illuminate\Support\Collection
    {
        return \App\Models\Catalog\Product::query()
            ->whereRaw('global_name::text ilike ?', ['%' . $search . '%'])
            ->limit($limit)
            ->get();
    }
}