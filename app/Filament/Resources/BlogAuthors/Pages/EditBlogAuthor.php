<?php

namespace App\Filament\Resources\BlogAuthors\Pages;

use App\Filament\Resources\BlogAuthors\BlogAuthorResource;
use App\Filament\Concerns\StripsSlugFormState;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBlogAuthor extends EditRecord
{
    protected static string $resource = BlogAuthorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    use StripsSlugFormState;
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->stripSlugFormState($data);
    }
}
