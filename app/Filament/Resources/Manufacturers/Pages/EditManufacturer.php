<?php

namespace App\Filament\Resources\Manufacturers\Pages;

use App\Filament\Resources\Manufacturers\ManufacturerResource;
use App\Filament\Support\AdminMenu\NavigationItem;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\On;

class EditManufacturer extends EditRecord
{
    protected static string $resource = ManufacturerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public static function getNavigationIcon(): string
    {
        return NavigationItem::Manufacturers->icon();
    }

    #[On('refresh-tabs')]
    public function refreshTabBadges(): void
    {
        // Empty method to refresh tab relation manager badges
    }
}
