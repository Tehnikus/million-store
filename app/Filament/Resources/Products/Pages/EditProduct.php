<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Catalog\ProductDescription;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use App\Filament\Concerns\StripsSlugFormState;


class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $storeId = Filament::getTenant()->id;

        $description = ProductDescription::query()
            ->where('product_id', $this->record->id)
            ->where('store_id', $storeId)
            ->first();

        $data['description'] = $description?->toArray() ?? [];

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $descriptionData = Arr::pull($data, 'description', []);

        $record->update($data);

        ProductDescription::updateOrCreate(
            ['product_id' => $record->id, 'store_id' => Filament::getTenant()->id],
            $descriptionData,
        );

        return $record;
    }

    use StripsSlugFormState;
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->stripSlugFormState($data);
    }

}
