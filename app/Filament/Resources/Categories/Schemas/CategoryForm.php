<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Catalog\Category;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use App\Filament\Schemas\LanguageTabs;
use App\Filament\Schemas\Tabs\DescriptionTab;
use App\Filament\Schemas\Tabs\FaqTab;
use App\Filament\Schemas\Tabs\HowToTab;
use App\Filament\Schemas\Tabs\FooterTab;
use App\Filament\Schemas\Tabs\ImagesTab;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\Toggle;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        $languages = Filament::getTenant()->languages()->wherePivot('is_active', true)->get();
        return $schema
            ->components([

                Select::make('parent_id')
                    ->label(__('admin.catalog.categories.fields.parent_id'))
                    ->relationship(
                        name: 'parentId', // Function name in Category model
                        titleAttribute: 'name',
                        ignoreRecord: true,
                        modifyQueryUsing: fn(Builder $query, $record) => $query->where('store_id', Filament::getTenant()->id)
                        // ->whereNot('parent_id', $record?->id),
                    )
                    ->getOptionLabelFromRecordUsing(fn(Category $record) => $record->name)
                    ->searchable()
                    ->preload()
                    ->columnSpanFull()
                    ->helperText(__('admin.catalog.categories.helpers.parent_id')),

                Toggle::make('is_active')
                    ->label(__('admin.catalog.categories.fields.is_active'))
                    ->default(true),
                Toggle::make('show_in_facets')
                    ->label(__('admin.catalog.categories.fields.show_in_facets'))
                    ->default(true),


                Tabs::make('category')
                    ->schema([
                        Tab::make(__('admin.common.tabs.content'))
                            ->schema([
                                LanguageTabs::make($languages, [
                                    [DescriptionTab::class, ['withSlug' => true]],
                                    FaqTab::class,
                                    HowToTab::class,
                                    FooterTab::class,

                                ])
                            ]),
                        Tab::make('images')
                            ->label(ImagesTab::label())
                            ->schema(ImagesTab::schema(['type' => 'category']))
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
