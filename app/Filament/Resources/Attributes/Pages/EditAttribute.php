<?php

namespace App\Filament\Resources\Attributes\Pages;

use App\Domain\Catalog\Actions\DeletionWarnings;
use App\Domain\Catalog\FacetType;
use App\Filament\Resources\Attributes\AttributeResource;
use App\Models\Catalog\Attribute;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\HtmlString;

class EditAttribute extends EditRecord
{
    protected static string $resource = AttributeResource::class;

    protected function getHeaderActions(): array
    {
        $store = Filament::getTenant();
        return [
            DeleteAction::make()
                ->modalDescription(
                    function (Attribute $record) use ($store): HtmlString {
                        $valueIds = $record->values->modelKeys();
                        return DeletionWarnings::affectedWarningMessage(FacetType::AttributeValue, $valueIds, $store->id, true);
                    }
                ),
        ];
    }
}
