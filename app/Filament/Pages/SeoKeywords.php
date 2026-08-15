<?php
namespace App\Filament\Pages;

use App\Models\Seo\Keyword;
use App\Models\Seo\KeywordGroup;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

use App\Filament\Support\AdminMenu\NavigationItem;
use App\Filament\Support\AdminMenu\HasCentralizedNavigation;

class SeoKeywords extends Page
{
    protected string $view = 'filament.pages.seo-keywords';

    public array $keywords      = [];
    public array $keywordGroups = [];
    public array $languages     = [];

    public function mount(): void
    {
        $storeId = Filament::getTenant()->id;

        $this->languages = Filament::getTenant()
            ->languages()
            ->wherePivot('is_active', true)
            ->get(['languages.id', 'languages.name', 'languages.locale'])
            ->toArray();

        $this->keywordGroups = KeywordGroup::where('store_id', $storeId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();

        $this->keywords = Keyword::where('store_id', $storeId)
            ->get(['id', 'keyword', 'url', 'language_id', 'keyword_group_id'])
            ->toArray();
    }

    /**
     * Принимает массив строк из таблицы, сохраняет батчем в транзакции.
     * Строка без id (или id <= 0) — новая запись, иначе — обновление существующей.
     */
    public function saveKeywords(array $rows): array
    {
        $storeId = Filament::getTenant()->id;
        $saved = [];

        DB::transaction(function () use ($rows, $storeId, &$saved) {
            foreach ($rows as $row) {
                $id = $row['id'] ?? null;

                $attributes = [
                    'store_id'         => $storeId,
                    'keyword'          => $row['keyword'] ?? '',
                    'url'              => $row['url'] ?? '',
                    'language_id'      => $row['language_id'] ?? null,
                    'keyword_group_id' => $row['keyword_group_id'] ?: null,
                ];

                $keyword = filled($id)
                    ? tap(Keyword::where('store_id', $storeId)->findOrFail($id))->update($attributes)
                    : Keyword::create($attributes);

                $saved[] = $keyword->fresh()->toArray();
            }
        });

        return $saved;
    }

    public function deleteKeywords(array $ids): void
    {
        Keyword::where('store_id', Filament::getTenant()->id)
            ->whereIn('id', $ids)
            ->delete();
    }

    public function saveKeywordGroup(string $name): array
    {
        $group = KeywordGroup::firstOrCreate(
            ['store_id' => Filament::getTenant()->id, 'name' => $name],
        );

        return $group->toArray();
    }

    public function deleteKeywordGroup(int $id): void
    {
        KeywordGroup::where('store_id', Filament::getTenant()->id)
            ->where('id', $id)
            ->delete();
    }

    use HasCentralizedNavigation;
    protected static function getMenuConfig(): NavigationItem
    {
        return NavigationItem::Keywords;
    }
}