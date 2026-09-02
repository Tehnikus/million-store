<?php

namespace App\Filament\Clusters\MetaEditor\Livewire;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

abstract class AbstractEntitiesTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    abstract protected function getEntitiesQuery(): Builder;

    /** @return array<\Filament\Tables\Columns\Column> */
    abstract protected function entityColumns(): array;

    abstract protected function toResultRow(Model $record): array;

    abstract protected function editRecordUrl(Model $record): ?string;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getEntitiesQuery())
            ->columns($this->entityColumns())
            ->filters([
                Filter::make('incomplete')
                    ->query(function(Builder $query) {
                        $languages = once(fn () => Filament::getTenant()->languages()->wherePivot('is_active', true)->pluck('locale'));
                        foreach ($languages as $locale) {
                            $query->whereNull("name->{$locale}")->orWhereNull("h1->{$locale}")->orWhereNull("meta_title->{$locale}")->orWhereNull("meta_description->{$locale}");
                        }
                        return $query;
                    })
                    ->label(__('admin.seo.meta_editor.filters.incomplete'))
                    ->toggle()
            ])
            ->recordActions([
                Action::make('edit')
                    ->url(fn (Model $record) => $this->editRecordUrl($record))
                    ->visible(fn (Model $record) => filled($this->editRecordUrl($record)))
                    ->openUrlInNewTab()
                    ->label(__('admin.seo.meta_editor.buttons.edit'))
                    ->tooltip(__('admin.seo.meta_editor.buttons.edit'))
                    ->icon('heroicon-o-pencil-square'),
                Action::make('addToResults')
                    ->action(fn (Model $record) => $this->dispatch(
                        'meta-editor:add-to-results',
                        row: $this->toResultRow($record),
                    ))
                    ->label(__('admin.seo.meta_editor.buttons.add_to_results'))
                    ->tooltip(__('admin.seo.meta_editor.buttons.add_to_results'))
                    ->icon('heroicon-o-arrow-right'),
            ])
            ->toolbarActions([
                BulkAction::make('addFilteredToResults')
                    ->label(__('admin.seo.meta_editor.buttons.add_to_results'))
                    ->icon('heroicon-o-arrow-right')
                    ->iconPosition(IconPosition::After)
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            $this->dispatch('meta-editor:add-to-results', row: $this->toResultRow($record));
                        }
                    }),
            ]);
    }

    // Makes pagination to have its own unique state in livewire
    // Required for both tables pagination to work correctly
    // Side note: Custom data tables (AbstractResultsTable) does not work correctly with explicitly set pagination
    // Some strange glitches happen there so I just left it default. Seems to work that way!
    protected function getTablePaginationPageName(): string
    {
        return 'entities_page';
    }

    public function render()
    {
        return view('filament.clusters.meta-editor.livewire.entities-table');
    }
}