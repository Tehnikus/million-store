<?php

namespace App\Filament\Clusters\MetaEditor\Livewire;

use App\Models\Catalog\Category;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\Layout\Grid;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Illuminate\Database\Eloquent\Model;

class CategoriesResultsTable extends AbstractResultsTable
{

    /**
     * Staging table columns in AbstractResultsTable::table()
     * TODO Maybe move columns entirely in AbstractResultsTable::table()?
     * @return \Filament\Tables\Columns\Layout\Grid[]
     */
    protected function resultColumns(): array
    {
        $languages = once(fn () => Filament::getTenant()->languages()->wherePivot('is_active', true)->pluck('locale'));

        return [
            Grid::make(['lg' => 1, 'xl' => 4])
                ->schema([
                    TextColumn::make('name')
                        ->weight(FontWeight::Bold)
                        ->color('primary')
                        ->columnSpanFull()
                        ->alignCenter()
                        ->size(TextSize::Medium),

                    Stack::make([
                        TextColumn::make('column_meta_title')
                            ->default(__('admin.common.fields.meta_title'))
                            ->color(Color::Gray)
                            ->size(TextSize::Small),
                        ...collect($languages)->map(fn ($locale) => TextInputColumn::make("meta_title.{$locale}")
                            ->getStateUsing(fn (array $record) => $record['meta_title'][$locale] ?? '')
                            ->updateStateUsing(fn ($state, array $record) => $this->updateResultField('meta_title', $state, $record, $locale))
                            ->prefix($locale)
                            ->placeholder(__('admin.common.fields.meta_title'))),
                    ])->space(2)->columnSpan(['lg' => 1, 'xl' => 1]),

                    Stack::make([
                        TextColumn::make('column_h1')
                            ->default(__('admin.common.fields.h1'))
                            ->color(Color::Gray)
                            ->size(TextSize::Small),
                        ...collect($languages)->map(fn ($locale) => TextInputColumn::make("h1.{$locale}")
                            ->getStateUsing(fn (array $record) => $record['h1'][$locale] ?? '')
                            ->updateStateUsing(fn ($state, array $record) => $this->updateResultField('h1', $state, $record, $locale))
                            ->prefix($locale)
                            ->placeholder(__('admin.common.fields.h1'))),
                    ])->space(2)->columnSpan(['lg' => 1, 'xl' => 1]),

                    Stack::make([
                        TextColumn::make('column_description')
                            ->default(__('admin.common.fields.meta_description'))
                            ->color(Color::Gray)
                            ->size(TextSize::Small),
                        ...collect($languages)->map(fn ($locale) => TextInputColumn::make("meta_description.{$locale}")
                            ->getStateUsing(fn (array $record) => $record['meta_description'][$locale] ?? '')
                            ->updateStateUsing(fn ($state, array $record) => $this->updateResultField('meta_description', $state, $record, $locale))
                            ->prefix($locale)
                            ->placeholder(__('admin.common.fields.meta_description'))),
                    ])->space(2)->columnSpan(['lg' => 1, 'xl' => 2]),
                ]),
        ];
    }

    /**
     * List of translatable fields so DB knows how to write changes in AbstractResultsTable::saveResults()
     * @return string[]
     */
    protected function translatableFields(): array
    {
        return ['name', 'meta_title', 'h1', 'meta_description'];
    }

    /**
     * Check if category exists before saving in AbstractResultsTable::saveResults()
     * @param int|string $id
     * @return Category|\stdClass|null
     */
    protected function resolveEntity(int|string $id): ?Model
    {
        return Category::query()
            ->where('store_id', Filament::getTenant()->id)
            ->whereKey($id)
            ->first();
    }
}