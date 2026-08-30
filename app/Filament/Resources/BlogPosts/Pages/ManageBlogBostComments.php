<?php

namespace App\Filament\Resources\BlogPosts\Pages;

use App\Filament\Resources\BlogComments\Schemas\BlogCommentForm;
use App\Filament\Resources\BlogComments\Tables\BlogCommentsTable;
use App\Filament\Resources\BlogPosts\BlogPostResource;
use App\Filament\Support\AdminMenu\NavigationItem;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Livewire\Livewire;

class ManageBlogBostComments extends ManageRelatedRecords
{
    protected static string $resource = BlogPostResource::class;

    protected static string $relationship = 'comments';

    public function form(Schema $schema): Schema
    {
        return BlogCommentForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return BlogCommentsTable::configure($table)
            ->headerActions([
                CreateAction::make(),
            ]);
    }

    public static function getNavigationIcon(): string
    {
        return NavigationItem::BlogComments->icon();
    }

    public static function getNavigationLabel(): string
    {
        return NavigationItem::BlogComments->labelPlural();
    }

    public static function getNavigationBadge(): ?string
    {
        return Livewire::current()->getRecord()->comments->count();
    }

}
