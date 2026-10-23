<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Domain\Catalog\Actions\DeletionWarnings;
use App\Domain\Catalog\FacetType;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Models\Catalog\Category;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\On;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        $store = Filament::getTenant();
        return [
            DeleteAction::make()
                ->modalDescription(fn(Category $record): HtmlString =>
                    DeletionWarnings::affectedWarningMessage(FacetType::Category, [$record->id], $store->id)),
        ];
    }
    
    protected function afterSave(): void
    {
        $this->refreshFormData(['images']);
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
