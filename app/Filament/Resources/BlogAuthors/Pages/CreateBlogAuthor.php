<?php

namespace App\Filament\Resources\BlogAuthors\Pages;

use App\Filament\Resources\BlogAuthors\BlogAuthorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBlogAuthor extends CreateRecord
{
    protected static string $resource = BlogAuthorResource::class;
}
