<?php

namespace App\Filament\Resources\BlogAuthors\Pages;

use App\Filament\Resources\BlogAuthors\BlogAuthorResource;
use App\Filament\Concerns\StripsSlugFormState;
use Filament\Resources\Pages\CreateRecord;

class CreateBlogAuthor extends CreateRecord
{
    protected static string $resource = BlogAuthorResource::class;
    use StripsSlugFormState;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->stripSlugFormState($data);
    }
}
