<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Support\AdminMenu\NavigationItem;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\On;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public static function getNavigationIcon(): string
    {
        return NavigationItem::Categories->icon();
    }

    #[On('refresh-tabs')]
    public function refreshTabBadges(): void
    {
        // Empty method to refresh tab relation manager badges
    }
}
