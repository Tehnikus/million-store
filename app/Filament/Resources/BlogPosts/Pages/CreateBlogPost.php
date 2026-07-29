<?php

namespace App\Filament\Resources\BlogPosts\Pages;

use App\Filament\Resources\BlogPosts\BlogPostResource;
use App\Filament\Concerns\StripsSlugFormState;
use Filament\Resources\Pages\CreateRecord;

class CreateBlogPost extends CreateRecord
{
    protected static string $resource = BlogPostResource::class;
    
    use StripsSlugFormState;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->stripSlugFormState($data);
    }
}
