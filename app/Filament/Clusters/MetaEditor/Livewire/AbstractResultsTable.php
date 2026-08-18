<?php

namespace App\Filament\Clusters\MetaEditor\Livewire;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

abstract class AbstractResultsTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public array $resultsTable = [];

    /** @return array<\Filament\Tables\Columns\Column> */
    abstract protected function resultColumns(): array;

    /** Translatable fields of current entity */
    abstract protected function translatableFields(): array;

    /** Resolve entites with Eloquent model */
    abstract protected function resolveEntity(int|string $id): ?Model;

    #[On('meta-editor:add-to-results')]
    public function addRow(array $row): void
    {
        $this->resultsTable[$row['id']] = $row;
    }

    protected function updateResultField(string $field, string $state, array $record, string $locale): void
    {
        $this->resultsTable[$record['id']][$field][$locale] = $state;
    }

    protected function removeRecordByKey(int|string $id): void
    {
        unset($this->resultsTable[$id]);
    }

    public function table(Table $table): Table
    {
        return $table
            // Uncomment this if pagination glitches FIX
            // ->records(fn () => array_values($this->resultsTable))
            // Comment this if pagination glitches
            ->records(function (int $page, int $recordsPerPage): LengthAwarePaginator {
                $records = collect($this->resultsTable)->values()->forPage($page, $recordsPerPage);

                return new LengthAwarePaginator(
                    $records,
                    total: count($this->resultsTable),
                    perPage: $recordsPerPage,
                    currentPage: $page,
                );
            })
            ->columns($this->resultColumns())
            ->recordActions([
                // TODO single generation by active formula
                Action::make('generate')
                    ->color('info')
                    ->icon('heroicon-s-puzzle-piece')
                    ->action(fn (array $record) => null),

                Action::make('delete')
                    ->color('danger')
                    ->icon('heroicon-s-no-symbol')
                    ->action(function (array $record): void {
                        $this->removeRecordByKey($record['id']);
                        $this->resetTable();
                    }),
            ])
            ->toolbarActions([
                Action::make('saveAll')
                    ->label(__('admin.common.buttons.save'))
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->action(fn () => $this->saveResults())
                    // ->visible(!empty($this->resultsTable))
                    ->visible(fn($records) => collect($this->resultsTable)->count() > 0)
                    ->accessSelectedRecords(),
            ])
            ->emptyStateHeading(__('admin.seo.meta_editor.helpers.empty_results_table_title'))
            ->emptyStateDescription(__('admin.seo.meta_editor.helpers.empty_results_table_descriptions'));
    }

    protected function saveResults(): void
    {
        if ($this->resultsTable === []) {
            return;
        }

        // TODO Make bulk update instead of foreach
        DB::transaction(function () {
            foreach ($this->resultsTable as $id => $row) {
                // Check if entity record exists
                $entity = $this->resolveEntity($id);

                // Skip record if it does not exist
                if (! $entity) {
                    continue;
                }

                foreach ($this->translatableFields() as $field) {
                    foreach ($row[$field] ?? [] as $locale => $value) {
                        $entity->setTranslation($field, $locale, $value);
                    }
                }

                $entity->save();
            }
        });

        $this->resultsTable = [];
        $this->resetTable();

        Notification::make()->success()->title(__('admin.messages.settings_saved'))->send();
    }

    public function render()
    {
        return view('filament.clusters.meta-editor.livewire.results-table');
    }
}