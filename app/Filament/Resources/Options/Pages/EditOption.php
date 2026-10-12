<?php

namespace App\Filament\Resources\Options\Pages;

use App\Domain\Catalog\Actions\PurgeDeletedCatalogValue;
use App\Domain\Catalog\FacetType;
use App\Filament\Resources\Options\OptionResource;
use App\Models\Catalog\Option;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\HtmlString;

class EditOption extends EditRecord
{
    protected static string $resource = OptionResource::class;

    protected function getHeaderActions(): array
    {
        $store = Filament::getTenant();
        return [
            DeleteAction::make()
                ->modalDescription(
                    function (Option $record) use ($store): HtmlString {
                        $valueIds = $record->values->modelKeys();
                        return PurgeDeletedCatalogValue::affectedWarningMessage(FacetType::OptionValue, $valueIds, $store->id, true);
                    }
                ),
        ];
    }
}
