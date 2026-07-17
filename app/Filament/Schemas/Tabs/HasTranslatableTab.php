<?php

namespace App\Filament\Schemas\Tabs;

interface HasTranslatableTab
{
    public static function label(): string;

    /** @return array<\Filament\Schemas\Components\Component> */
    public static function schema(string $locale, array $config = []): array;
}