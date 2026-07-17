<?php

namespace App\Filament\Resources\BlogTags\Pages;

use App\Filament\Concerns\SyncsSlugFields;
use App\Filament\Resources\BlogTags\BlogTagResource;
use App\Models\BlogTag;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;
class CreateBlogTag extends CreateRecord
{
    use SyncsSlugFields;
    use RedirectsToIndex;

    protected static string $resource = BlogTagResource::class;

    protected array $pendingSlugs = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingSlugs = $this->pullSlugsFromData($data);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->syncSlugs($this->pendingSlugs, BlogTag::class, $this->record->id);
    }
}