<?php

namespace App\Filament\Resources\BlogTags\Pages;

use App\Filament\Resources\BlogTags\BlogTagResource;
use App\Filament\Concerns\StripsSlugFormState;
use Filament\Resources\Pages\CreateRecord;
class CreateBlogTag extends CreateRecord
{
    protected static string $resource = BlogTagResource::class;

    use StripsSlugFormState;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->stripSlugFormState($data);
    }
}