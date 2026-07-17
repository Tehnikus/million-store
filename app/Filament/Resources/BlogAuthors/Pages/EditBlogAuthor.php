<?php

namespace App\Filament\Resources\BlogAuthors\Pages;

use App\Filament\Resources\BlogAuthors\BlogAuthorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Models\BlogAuthor;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\SyncsSlugFields;
use App\Filament\Concerns\RedirectsToIndex;

class EditBlogAuthor extends EditRecord
{
    use SyncsSlugFields;
    use RedirectsToIndex;
    protected static string $resource = BlogAuthorResource::class;
    protected array $pendingSlugs = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->loadSlugsIntoData($data, BlogAuthor::class, $this->record->id);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingSlugs = $this->pullSlugsFromData($data);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->syncSlugs($this->pendingSlugs, BlogAuthor::class, $this->record->id);
    }
}
