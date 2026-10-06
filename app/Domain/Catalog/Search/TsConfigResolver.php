<?php

namespace App\Domain\Catalog\Search;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Turns languages.ts_config (a free-form string) into a text search configuration that really exists in Postgres.
 * Shared by SearchIndexer (writing) and ProductSearch (reading) so both always use the same config for a language.
 *
 * A missing dictionary makes the ::regconfig cast fail, which would break saving a product
 * (inside a transaction it would also abort it) or a search request. So we fall back to 'simple'
 * (no stemming, still searchable) and log it.
 *
 * Checked against the pg_ts_config catalog, limited to configs visible in the current search_path -
 * exactly the ones the ::regconfig cast can resolve. Schema-qualified names ('public.ukrainian')
 * are not supported; install the dictionary into a schema on the search_path instead.
 *
 * Do NOT bind as a singleton (Octane): the instance caches results, and dictionaries can be installed at runtime.
 */
class TsConfigResolver
{
    /** @var array<string, string> requested name => name that actually exists in Postgres */
    private array $resolved = [];

    public function resolve(?string $name): string
    {
        $wanted = ($name !== null && $name !== '') ? $name : 'simple';

        if (! isset($this->resolved[$wanted])) {
            $exists = (bool) DB::scalar(
                'SELECT EXISTS (SELECT 1 FROM pg_ts_config WHERE cfgname = ? AND pg_ts_config_is_visible(oid))',
                [$wanted]
            );

            if (! $exists) {
                Log::warning("Search: text search config '{$wanted}' does not exist in Postgres, using 'simple'.");
            }

            $this->resolved[$wanted] = $exists ? $wanted : 'simple';
        }

        return $this->resolved[$wanted];
    }
}