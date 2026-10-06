<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Filament\Resources\BlogComments\Schemas\BlogCommentForm;
use App\Filament\Resources\BlogComments\Tables\BlogCommentsTable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class BlogCommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    public function form(Schema $schema): Schema
    {
        return BlogCommentForm::configure($schema, $this->getOwnerRecord()->id);
    }

    public function table(Table $table): Table
    {
        return BlogCommentsTable::configure($table)
            ->searchable(false)
            ->recordActions([
                EditAction::make()->modalHeading(__('admin.blog.posts.tabs.comments.edit_modal_heading')),
                DeleteAction::make()->after(fn () => $this->dispatch('refresh-tabs')),
            ])
            ->headerActions([
                CreateAction::make()->after(fn () => $this->dispatch('refresh-tabs'))->modalHeading(__('admin.blog.posts.tabs.comments.create_modal_heading')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->after(fn () => $this->dispatch('refresh-tabs')),
                ]),
            ]);
    }

    protected function getTableHeading(): string
    {
        return __('admin.blog.posts.tabs.comments.label');
    }
}
