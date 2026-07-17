<?php

namespace App\Filament\Resources\StoreInfoPages\Schemas;
use Filament\Facades\Filament;
use App\Filament\Schemas\LanguageTabs;
use App\Filament\Schemas\Tabs\DescriptionTab;
use App\Filament\Schemas\Tabs\FaqTab;
use App\Filament\Schemas\Tabs\HowToTab;
use App\Filament\Schemas\Tabs\FooterTab;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class StoreInfoPageForm
{
    public static function configure(Schema $schema): Schema
    {
        $languages = Filament::getTenant()
            ->languages()
            ->wherePivot('is_active', true)
            ->get();
        return $schema
            ->components([
                Toggle::make('is_active')
                    ->label(__('admin.blog.posts.fields.is_active'))
                    ->default(true)
                    ->columnSpanFull(),
                LanguageTabs::make($languages, [
                    [DescriptionTab::class, ['withSlug' => true]],
                    FaqTab::class,
                    HowToTab::class,
                    FooterTab::class,

                ])
                ->columnSpanFull()
            ]);
    }
}
