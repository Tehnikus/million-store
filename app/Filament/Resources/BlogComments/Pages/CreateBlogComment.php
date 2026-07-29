<?php

namespace App\Filament\Resources\BlogComments\Pages;

use App\Filament\Resources\BlogComments\BlogCommentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBlogComment extends CreateRecord
{
    protected static string $resource = BlogCommentResource::class;
}
