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

    public array $keywordGroups = [];
    public array $languages = [];

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
    }

    public function getKeywords(): array
    {
        return Keyword::where('store_id', Filament::getTenant()->id)
            ->get(['id', 'keyword', 'url', 'language_id', 'keyword_group_id'])
            ->toArray();
    }

    /**
     * @param array<int, array{id: ?int, keyword: string, url: string, language_id: int, keyword_group_id: ?int}> $rows
     */
    public function saveKeywords(array $rows): array
    {
        $storeId = Filament::getTenant()->id;
        $saved = [];

        DB::transaction(function () use ($rows, $storeId, &$saved) {
            foreach ($rows as $row) {
                $attributes = [
                    'store_id'         => $storeId,
                    'keyword'          => $row['keyword'],
                    'url'              => $row['url'],
                    'language_id'      => $row['language_id'],
                    'keyword_group_id' => $row['keyword_group_id'],
                ];

                $keyword = filled($row['id'] ?? null)
                    ? tap(Keyword::where('store_id', $storeId)->findOrFail($row['id']))->update($attributes)
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
        $group = KeywordGroup::firstOrCreate([
            'store_id' => Filament::getTenant()->id,
            'name'     => $name,
        ]);

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