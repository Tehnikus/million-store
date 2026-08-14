<?php

namespace App\Filament\Resources\FacetPages\Tables;

use App\Domain\Catalog\FacetType;
use App\Filament\Support\Columns\ConversionImageColumn;
use App\Filament\Support\Columns\MultilangTextColumn;
use App\Models\Catalog\FacetPage;
use App\Models\Catalog\{AttributeValue, Category, Manufacturer, OptionValue, Tag};
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Context;

class FacetPagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('facetIndex'))
            ->columns([
                ConversionImageColumn::make('images')
                    ->conversion('miniature')
                    ->label(__('admin.common.fields.image')),

                MultilangTextColumn::make('name')
                    ->recordColumnAll(fn ($record) => $record->getTranslations('name') ?? [])
                    ->wrapHeader()
                    ->label(__('admin.catalog.facet_pages.model_label_singular')),

                TextColumn::make('facetIndex')
                    ->label(__('admin.catalog.facet_pages.fields.facet_list'))
                    ->state(function (FacetPage $record) {
                        return $record->facetIndex->map(function ($item) use ($record) {
                            $type  = $item->facet_type_id;
                            $names = self::namesFor($type, $record->store_id);

                            return [
                                'type' => $type,
                                'name' => $names[$item->facet_value_id] ?? "#{$item->facet_value_id}",
                            ];
                        })
                        ->sortBy(fn ($facet) => $facet['type']->sortPriority())
                        ->values()
                        ->all();
                    })
                    ->formatStateUsing(fn (array $state) => $state['name'])
                    ->color(fn (array $state) => $state['type']->getColor())
                    ->icon(fn (array $state) => $state['type']->getIcon())
                    ->size(TextSize::Medium)
                    ->badge()
                    ->width('300px')
                    ->wrap()
                    ->wrapHeader(),

                ToggleColumn::make('is_active')
                    ->sortable()
                    ->width('100px')
                    ->alignment(Alignment::Center)
                    ->label(__('admin.catalog.facet_pages.fields.is_active')),

            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function namesFor(FacetType $type, int $storeId): array
    {
        $key = "facet_entity_names:{$type->value}:{$storeId}";

        if (Context::has($key)) {
            return Context::get($key);
        }

        $names = self::loadNames($type, $storeId);
        Context::add($key, $names);

        return $names;
    }

    private static function loadNames(FacetType $type, int $storeId): array
    {
        return match ($type) {
            // Get name of facets
            FacetType::Category => Category::query()->where('store_id', $storeId)
                ->get()->mapWithKeys(fn($m) => [$m->id => $m->name])->all(),

            FacetType::Manufacturer => Manufacturer::query()->where('store_id', $storeId)
                ->get()->mapWithKeys(fn($m) => [$m->id => $m->name])->all(),

            FacetType::Tag => Tag::query()->where('store_id', $storeId)
                ->get()->mapWithKeys(fn($m) => [$m->id => $m->name])->all(),

            // Get parent entity name for the following facets
            FacetType::Attribute => AttributeValue::query()
                ->where('store_id', $storeId)
                ->with('attribute') // relation function name to parent model Attribute in in AttributeValue model
                ->get()
                ->mapWithKeys(fn($v) => [$v->id => self::withParentName($v->attribute?->name, $v->name)])
                ->all(),

            FacetType::Option => OptionValue::query()
                ->where('store_id', $storeId)
                ->with('option') // relation function name to parent model Option in in OptionValue model
                ->get()
                ->mapWithKeys(fn($v) => [$v->id => self::withParentName($v->option?->name, $v->name)])
                ->all(),

            // Facets that don't have own dynamic name, like "featured" or "discount"
            default => [$type->getLabel()]
        };
    }

    /**
     * "Parent: value" badge for options and attributes
     */
    private static function withParentName(?string $parentName, string $ownName): string
    {
        return filled($parentName) ? "{$parentName}: {$ownName}" : $ownName;
    }

}
