<?php

namespace App\Filament\Resources\BlogComments\Pages;

use App\Filament\Resources\BlogComments\BlogCommentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBlogComment extends EditRecord
{
    protected static string $resource = BlogCommentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
