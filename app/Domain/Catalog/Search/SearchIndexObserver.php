<?php

namespace App\Domain\Catalog\Search;

use App\Models\Catalog\Attribute;
use App\Models\Catalog\AttributeValue;
use App\Models\Catalog\Category;
use App\Models\Catalog\Manufacturer;
use App\Models\Catalog\Option;
use App\Models\Catalog\OptionValue;
use App\Models\Catalog\Tag;
use App\Models\Global\Language;
use Illuminate\Database\Eloquent\Model;

/**
 * Keeps product_search_index in sync when an entity that is part of a search document changes.
 * One class for all seven entity models (see map()); register it once with SearchIndexObserver::register().
 *
 *  - updated: only a changed NAME matters (names are the only thing of these entities that is indexed),
 *             and only the languages whose name actually changed are rebuilt.
 *  - deleted: every row that contained the entity is rebuilt in all languages.
 *
 * Rows are found through search_included_refs (GIN index), see SearchIndexer::rebuildForEntity().
 *
 * Order of listeners on delete does not matter: HasFacetIndexCleanup may or may not have removed the facet_index
 * rows yet, but the entity row itself is already gone, and SearchIndexer drops entities it cannot load.
 * Attribute and Option have no cleanup trait (their values are removed by the database cascade, which fires no
 * model events), so rebuilding on the group's own delete is what cleans their products' documents.
 *
 * This is the single place to switch to queued rebuilds later: replace the call in rebuild() with a dispatch.
 *
 * Observer instances live as long as the app (Octane), so no state is kept here and SearchIndexer is resolved
 * on every call, never injected into the constructor.
 *
 * Not triggered by mass updates/deletes that bypass models (Category::query()->update(...)).
 */
class SearchIndexObserver
{
    /**
     * @return array<class-string<Model>, SearchRefType>
     */
    public static function map(): array
    {
        return [
            Category::class       => SearchRefType::Category,
            Manufacturer::class   => SearchRefType::Manufacturer,
            Tag::class            => SearchRefType::Tag,
            Attribute::class      => SearchRefType::Attribute,
            AttributeValue::class => SearchRefType::AttributeValue,
            Option::class         => SearchRefType::Option,
            OptionValue::class    => SearchRefType::OptionValue,
        ];
    }

    /**
     * Call once from AppServiceProvider::boot().
     */
    public static function register(): void
    {
        foreach (array_keys(static::map()) as $modelClass) {
            $modelClass::observe(static::class);
        }
    }

    public function updated(Model $model): void
    {
        // Inside "updated" the changes are already synced (wasChanged works), the original values are still the old ones
        if (! $model->wasChanged('name')) {
            return;
        }

        $locales = static::changedLocales(
            static::decode($model->getRawOriginal('name')),
            static::decode($model->getTranslations('name')),
        );

        if ($locales === []) {
            return;
        }

        $languageIds = Language::query()->whereIn('locale', $locales)->pluck('id')->all();

        if ($languageIds === []) {
            return;
        }

        $this->rebuild($model, $languageIds);
    }

    public function deleted(Model $model): void
    {
        $this->rebuild($model, null);
    }

    /**
     * @param int[]|null $languageIds  null = all languages
     */
    private function rebuild(Model $model, ?array $languageIds): void
    {
        $type = static::map()[$model::class] ?? null;

        if ($type === null) {
            return;
        }

        app(SearchIndexer::class)->rebuildForEntity($type, (int) $model->getKey(), $languageIds);
    }

    /**
     * Locales whose text differs (added, removed or edited). Whitespace-only edits do not count:
     * the indexer normalizes whitespace anyway.
     *
     * @param array<string, mixed> $old
     * @param array<string, mixed> $new
     * @return string[]
     */
    public static function changedLocales(array $old, array $new): array
    {
        $locales = array_unique([...array_keys($old), ...array_keys($new)]);

        return array_values(array_filter(
            array_map('strval', $locales),
            fn (string $locale) => trim((string) ($old[$locale] ?? '')) !== trim((string) ($new[$locale] ?? ''))
        ));
    }

    /**
     * Translatable attribute as stored (JSON string) or already decoded (array) -> array.
     *
     * @return array<string, mixed>
     */
    public static function decode(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }
}