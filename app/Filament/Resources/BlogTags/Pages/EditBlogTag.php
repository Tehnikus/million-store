<?php

namespace App\Filament\Resources\BlogTags\Pages;

use App\Filament\Resources\BlogTags\BlogTagResource;
use App\Filament\Concerns\StripsSlugFormState;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBlogTag extends EditRecord
{
    protected static string $resource = BlogTagResource::class;

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