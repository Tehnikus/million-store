<?php

namespace App\Filament\Resources\Tags\Pages;

use App\Filament\Resources\Tags\TagResource;
use App\Filament\Support\AdminMenu\NavigationItem;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\On;

class EditTag extends EditRecord
{
    protected static string $resource = TagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public static function getNavigationIcon(): string
    {
        return NavigationItem::Tags->icon();
    }

    #[On('refresh-tabs')]
    public function refreshTabBadges(): void
    {
        // Empty method to refresh tab relation manager badges
    }
}
