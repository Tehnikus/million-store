<?php

namespace App\Filament\Resources\BlogAuthors\Pages;

use App\Filament\Resources\BlogAuthors\BlogAuthorResource;
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
}
