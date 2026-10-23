<?php

namespace App\Filament\Resources\Tags\Pages;

use App\Domain\Catalog\Actions\DeletionWarnings;
use App\Domain\Catalog\FacetType;
use App\Filament\Resources\Tags\TagResource;
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Models\Catalog\Tag;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\On;

class EditTag extends EditRecord
{
    protected static string $resource = TagResource::class;

    protected function getHeaderActions(): array
    {
        $store = Filament::getTenant();
        return [
            DeleteAction::make()
                ->modalDescription(fn(Tag $record): HtmlString =>
                    DeletionWarnings::affectedWarningMessage(FacetType::Tag, [$record->id], $store->id)),
        ];
    }

    public static function getNavigationIcon(): string
    {
        return NavigationItem::Tags->icon();
    }

    protected function afterSave(): void
    {
        $this->refreshFormData(['images']);
    }

    #[On('refresh-tabs')]
    public function refreshTabBadges(): void
    {
        // Empty method to refresh tab relation manager badges
    }
}
