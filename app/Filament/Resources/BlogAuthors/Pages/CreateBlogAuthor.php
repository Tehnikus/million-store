<?php

namespace App\Filament\Resources\BlogAuthors\Pages;

use App\Models\BlogAuthor;
use App\Filament\Resources\BlogAuthors\BlogAuthorResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\SyncsSlugFields;
use App\Filament\Concerns\RedirectsToIndex;

class CreateBlogAuthor extends CreateRecord
{
    use SyncsSlugFields;
    use RedirectsToIndex;
    protected static string $resource = BlogAuthorResource::class;

    protected array $pendingSlugs = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingSlugs = $this->pullSlugsFromData($data);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->syncSlugs($this->pendingSlugs, BlogAuthor::class, $this->record->id);
    }
}
