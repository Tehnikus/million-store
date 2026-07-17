<?php

namespace App\Filament\Resources\BlogComments\Pages;

use App\Filament\Resources\BlogComments\BlogCommentResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateBlogComment extends CreateRecord
{
    use RedirectsToIndex;
    protected static string $resource = BlogCommentResource::class;
}
