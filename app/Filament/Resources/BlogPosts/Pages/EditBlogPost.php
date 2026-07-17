<?php

namespace App\Filament\Resources\BlogPosts\Pages;

use App\Models\BlogPost;
use App\Filament\Resources\BlogPosts\BlogPostResource;
use App\Filament\Concerns\SyncsSlugFields;
use App\Filament\Concerns\RedirectsToIndex;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBlogPost extends EditRecord
{
    use SyncsSlugFields;
    use RedirectsToIndex;

    protected static string $resource = BlogPostResource::class;
    protected array $pendingSlugs = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->loadSlugsIntoData($data, BlogPost::class, $this->record->id);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingSlugs = $this->pullSlugsFromData($data);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->syncSlugs($this->pendingSlugs, BlogPost::class, $this->record->id);
    }
}
