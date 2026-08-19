<?php

namespace App\Filament\Clusters\MetaEditor\Livewire;

use App\Domain\Seo\ApplyMetaFormula;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
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

/**
 * Staging table for MetaEditor.
 * Stores rows selected for meta tag edition/generation.
 * Rows are added by user from entitesTable
 * No changes to DB applied until saveResults() is called
 */
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

    /** Add row to staging table */
    #[On('meta-editor:add-to-results')]
    public function addRow(array $row): void
    {
        $this->resultsTable[$row['id']] = $row;
    }

    /**
     * Apply formula row action in repeater
     */
    #[On('meta-editor:apply-formula')]
    public function applyFormulaToResults(array $formula): void
    {
        
        $targetField = $formula['target_field'];
        $locale      = $formula['locale'];

        foreach ($this->resultsTable as $id => $row) {
            $result = ApplyMetaFormula::apply($formula['formula'], $this->buildFormulaVars($row, $locale));

            $this->resultsTable[$id][$targetField][$locale] = $result['text'];
            $this->resultsTable[$id]['has_error'] = $result['errors'] !== [];
            $this->resetTable();
        }
        
    }

    /**
     * Formula variables
     */
    protected function buildFormulaVars(array $row, string $locale): array
    {
        return [
            'name'  => $row['name'][$locale] ?? null,
            'store' => once(fn() => Filament::getTenant()->name),
        ];
    }

    protected function updateResultField(string $field, mixed $state, array $record, string $locale): void
    {
        $this->resultsTable[$record['id']][$field][$locale] = $state ?? '';
    }

    protected function removeRecordByKey(int|string $id): void
    {
        unset($this->resultsTable[$id]);
    }

    public function table(Table $table): Table
    {
        return $table
            // Pagination
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
            // Rows background color
            ->recordClasses(function ($record) {
                return (!empty($record['has_error']) && $record['has_error'] == true)
                    ? 'bg-danger-300 dark:bg-danger-600' 
                    : 'bg-success-300 dark:bg-success-600';
            })
            // Row actions
            ->recordActions([
                // TODO single generation by active formula
                Action::make('generate')
                    ->color('info')
                    ->icon('heroicon-s-puzzle-piece')
                    ->action(fn (array $record) => null),
                // Remove row from staging table
                Action::make('delete')
                    ->color('danger')
                    ->icon('heroicon-s-no-symbol')
                    ->action(function (array $record): void {
                        $this->removeRecordByKey($record['id']);
                        $this->resetTable();
                    }),
            ])
            // Toolbar actions
            ->toolbarActions([

                // Save all staging changes
                Action::make('saveAll')
                    ->label(__('admin.seo.meta_editor.buttons.save_staging'))
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->action(fn () => $this->saveResults())
                    ->visible(fn($records) => collect($this->resultsTable)->count() > 0)
                    ->accessSelectedRecords(),

                // Clear all staging rows
                Action::make('clearAll')
                    ->label(__('admin.seo.meta_editor.buttons.clear_staging'))
                    ->color('danger')
                    ->icon('heroicon-o-no-symbol')
                    ->action(function() {$this->resultsTable = []; $this->resetTable();})
                    ->visible(fn($records) => collect($this->resultsTable)->count() > 0)
                    ->accessSelectedRecords(),

                // BulkAction::make('feature')
                //     ->requiresConfirmation()
                //     ->action(function ($records): void {
                //         // Do something with the collection of `$records` data
                //     }),
            ])
            ->emptyStateHeading(__('admin.seo.meta_editor.helpers.empty_results_table_title'))
            ->emptyStateDescription(__('admin.seo.meta_editor.helpers.empty_results_table_descriptions'))
            ->emptyStateIcon('heroicon-o-clock');
    }

    protected function saveResults(): void
    {
        if ($this->resultsTable === []) {
            return;
        }

        $skipped = [];

        DB::transaction(function () use (&$skipped) {
            foreach ($this->resultsTable as $id => $row) {
                if ($row['has_error'] ?? false) {
                    $skipped[] = $row;
                    continue;
                }

                $entity = $this->resolveEntity($id);

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

        $this->resultsTable = $skipped;
        $this->resetTable();

        count($skipped) > 0
            ? Notification::make()->warning()->title(__('admin.seo.meta_editor.messages.saved_with_errors', ['count' => count($skipped)]))->send()
            : Notification::make()->success()->title(__('admin.seo.meta_editor.messages.staging_saved'))->send();
    }

    public function render()
    {
        return view('filament.clusters.meta-editor.livewire.results-table');
    }
}