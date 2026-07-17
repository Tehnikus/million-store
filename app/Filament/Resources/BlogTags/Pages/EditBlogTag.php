<?php

namespace App\Filament\Resources\BlogTags\Pages;

use App\Models\BlogTag;
use App\Filament\Resources\BlogTags\BlogTagResource;
use App\Filament\Concerns\SyncsSlugFields;
use App\Filament\Concerns\RedirectsToIndex;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBlogTag extends EditRecord
{
    use RedirectsToIndex;
    use SyncsSlugFields;

    protected static string $resource = BlogTagResource::class;

    protected array $pendingSlugs = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->loadSlugsIntoData($data, BlogTag::class, $this->record->id);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingSlugs = $this->pullSlugsFromData($data);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->syncSlugs($this->pendingSlugs, BlogTag::class, $this->record->id);
    }
}