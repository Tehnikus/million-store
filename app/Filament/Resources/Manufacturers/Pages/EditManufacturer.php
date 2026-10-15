<?php

namespace App\Filament\Resources\Manufacturers\Pages;

use App\Domain\Catalog\Actions\DeletionWarnings;
use App\Domain\Catalog\FacetType;
use App\Filament\Resources\Manufacturers\ManufacturerResource;
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Models\Catalog\Manufacturer;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\On;

class EditManufacturer extends EditRecord
{
    protected static string $resource = ManufacturerResource::class;

    protected function getHeaderActions(): array
    {
        $store = Filament::getTenant();
        return [
            DeleteAction::make()
                ->modalDescription(fn(Manufacturer $record): HtmlString =>
                    DeletionWarnings::affectedWarningMessage(FacetType::Manufacturer, [$record->id], $store->id)),
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
