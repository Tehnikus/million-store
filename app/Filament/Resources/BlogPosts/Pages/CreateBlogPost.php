<?php

namespace App\Filament\Resources\BlogPosts\Pages;

use App\Models\BlogPost;
use App\Filament\Resources\BlogPosts\BlogPostResource;
use App\Filament\Concerns\SyncsSlugFields;
use App\Filament\Concerns\RedirectsToIndex;
use Filament\Resources\Pages\CreateRecord;

class CreateBlogPost extends CreateRecord
{
    use SyncsSlugFields;
    use RedirectsToIndex;
    protected static string $resource = BlogPostResource::class;
    protected array $pendingSlugs = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingSlugs = $this->pullSlugsFromData($data);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->syncSlugs($this->pendingSlugs, BlogPost::class, $this->record->id);
    }
}
