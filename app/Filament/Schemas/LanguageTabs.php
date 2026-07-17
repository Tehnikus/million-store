<?php

namespace App\Filament\Schemas;

use App\Filament\Schemas\Tabs\HasTranslatableTab;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

class LanguageTabs
{
    public static function make(iterable $languages, array $tabProviders): Tabs
    {
        // Normalize: every provider either class-string, or [class-string, $config]
        $providers = collect($tabProviders)->map(
            fn($provider) => is_array($provider) ? $provider : [$provider, []]
        );

        return Tabs::make('translations')
            ->tabs(
                collect($languages)->map(
                    fn($language) =>
                    Tab::make($language->locale)
                        ->label("{$language->image} {$language->name}")
                        ->schema(
                            $providers->count() === 1
                            ? $providers->first()[0]::schema(
                                $language->locale,
                                [...$providers->first()[1], 'language_id' => $language->id]
                            )
                            : [
                                Tabs::make("translations.{$language->locale}")
                                    ->tabs(
                                        $providers->map(function (array $pair) use ($language) {
                                            [$provider, $config] = $pair;

                                            return Tab::make("{$provider}.{$language->locale}")
                                                ->label($provider::label())
                                                ->schema($provider::schema(
                                                    $language->locale,
                                                    [...$config, 'language_id' => $language->id]
                                                ));
                                        })->all()
                                    ),
                            ]
                        )
                )->all()
            );
    }
}