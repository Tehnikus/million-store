<?php

namespace App\Filament\Resources\BlogTags\Pages;

use App\Filament\Resources\BlogTags\BlogTagResource;
use App\Filament\Support\AdminMenu\NavigationItem;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\On;

class EditBlogTag extends EditRecord
{
    protected static string $resource = BlogTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $this->refreshFormData(['images']);
    }

    public static function getNavigationIcon(): string
    {
        return NavigationItem::BlogTags->icon();
    }

    #[On('refresh-tabs')]
    public function refreshTabBadges(): void
    {
        // Empty method to refresh tab relation manager badges
    }
}