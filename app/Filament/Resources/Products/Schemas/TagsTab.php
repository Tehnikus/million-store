<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Catalog\Tag;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Collection;


class TagsTab
{
    public static function schema($storeId): array
    {
        return [
            Repeater::make('facet_tags')
                ->label(__('admin.catalog.products.fields.product_tags'))
                ->table([
                    TableColumn::make(__('admin.catalog.products.fields.tag')),
                ])
                ->schema([
                    Select::make('facet_value_id')
                        ->label(__('admin.catalog.products.fields.tag'))
                        ->options(fn () => static::tagChoices($storeId))
                        ->searchable()
                        ->preload()
                        ->required()
                        // Restricts same tag selection
                        ->distinct()
                        ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                    Hidden::make('facet_group_id')
                        ->default(0)
                ])
                ->reorderable(true) // repeater elements order = sort_order
                ->addActionLabel(__('admin.catalog.products.buttons.add_tag'))
                ->columnSpanFull()
                ->defaultItems(0)
                ->helperText(__('admin.catalog.products.helpers.facet_tags')),
        ];
    }

    // Cache option list for single request or multiple requests if Octane is used
    protected static function tagChoices(int $storeId): Collection
    {
        $key = "tag_choices.{$storeId}";

        if (Context::has($key)) {
            return collect(Context::get($key));
        }

        $choices = Tag::query()
            ->where('store_id', $storeId)
            ->get()
            ->mapWithKeys(fn (Tag $tag) => [$tag->id => $tag->name]);

        Context::add($key, $choices->all());

        return $choices;
    }

    public static function label(): string
    {
        return __('admin.catalog.products.tabs.tags');
    }
}