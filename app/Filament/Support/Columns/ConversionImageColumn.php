<?php

namespace App\Filament\Support\Columns;

use Filament\Tables\Columns\ImageColumn;
use Filament\Support\Enums\Alignment;

/**
 * Miniature from images column with HasProcessedImages
 * Returns conversions.miniature of the first image
 */
class ConversionImageColumn extends ImageColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->disk('public')
            ->imageHeight(70)
            ->checkFileExistence(false)
            ->alignment(Alignment::Center)
            ->width('100px')
            ->extraImgAttributes(['loading' => 'lazy', 'style' => 'border-radius: .5rem; margin: -0.7rem 0;'])
            ->label(__('admin.common.fields.image'));
    }

    public function conversion(string $key): static
    {
        $this->state(fn ($record) => collect($record->images ?? [])
            ->pluck("conversions.{$key}")
            ->filter()
            ->values()
            ->first()
        );

        return $this;
    }
}