<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use App\Filament\Concerns\StripsSlugFormState;

class CreateProduct extends CreateRecord
{
    use StripsSlugFormState;
    protected static string $resource = ProductResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $descriptionData = Arr::pull($data, 'description', []);
        $descriptionData = $this->stripSlugFormState($descriptionData);
        $product = static::getModel()::create($data);

        $product->descriptions()->create([
            ...$descriptionData,
            'store_id' => Filament::getTenant()->id,
        ]);

        return $product;
    }

}
