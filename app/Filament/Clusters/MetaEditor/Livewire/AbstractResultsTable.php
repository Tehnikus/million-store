<?php

namespace App\Filament\Clusters\MetaEditor\Livewire;

use App\Domain\Seo\ApplyMetaFormula;
use App\Filament\Clusters\MetaEditor\Pages\AbstractMetaTagsFormulaPage;
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Models\Seo\MetaTagFormula;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\Layout\Grid;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Filament\Tables\Filters\Filter;


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

    /**
     * Staging table layout and actions
     * @param \Filament\Tables\Table $table
     * @return \Filament\Tables\Table
     */
    public function table(Table $table): Table
    {
        return $table
            // Pagination
            // Uncomment this if pagination glitches FIX
            // ->records(fn () => array_values($this->resultsTable))
            // Comment this if pagination glitches
            ->records(function (int $page, int $recordsPerPage, array $filters, ?string $search): LengthAwarePaginator {
                $records = collect($this->resultsTable)->values()->forPage($page, $recordsPerPage);

                if (filled($this->tableSearch)) {
                    $records = $records->filter(fn (array $row) => $this->rowMatchesSearch($row, $this->tableSearch));
                }

                if ($filters['incomplete']['isActive'] ?? false) {
                    $records = $records->filter(fn (array $row) => $this->rowHasEmptyField($row));
                }

                return new LengthAwarePaginator(
                    $records,
                    total: count($this->resultsTable),
                    perPage: $recordsPerPage,
                    currentPage: $page,
                );
            })
            ->filters([
                Filter::make('incomplete')
                    ->label(__('admin.seo.meta_editor.filters.incomplete'))
                    ->toggle(),
            ])
            ->columns($this->resultColumns())
            // Rows background color
            ->recordClasses(fn ($record) => (!empty($record['has_error']) && $record['has_error'] == true) ? 'meta-editor-row-error' : (!isset($record['has_error']) ? '' : 'meta-editor-row-ok'))
            // Row actions
            ->recordActions([

                Action::make('delete')
                    ->action(function (array $record): void {
                        $this->removeRecordByKey($record['id']);
                        $this->resetTable();
                    })
                    ->color('danger')
                    ->icon('heroicon-s-no-symbol')
                    ->label(__('admin.seo.meta_editor.buttons.delete'))
                    
            ])
            // Toolbar actions
            ->toolbarActions([

                // Save all staging changes
                Action::make('saveAll')
                    ->label(__('admin.seo.meta_editor.buttons.save_staging'))
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->action(fn () => $this->saveResults())
                    ->visible(fn() => $this->resultsTable !== [])
                    ->accessSelectedRecords(),

                // Clear all staging rows
                Action::make('clearAll')
                    ->label(__('admin.seo.meta_editor.buttons.clear_staging'))
                    ->color('danger')
                    ->icon('heroicon-o-no-symbol')
                    ->action(function() {$this->resultsTable = []; $this->resetTable();})
                    ->visible(fn() => $this->resultsTable !== [])
                    ->accessSelectedRecords(),

                // Action::make('applyFormula')
                //     ->label(__('admin.seo.meta_editor.buttons.apply_formula_all'))
                //     ->icon(NavigationItem::MetaEditor->icon())
                //     ->color('info')
                //     ->visible(fn() => $this->resultsTable !== [])
                //     ->schema($this->generateModalSchema())
                //     ->action(function (array $data) {
                //         $formula = $this->availableFormulas()->firstWhere('id', (int) $data['formula_id']);
                        
                //         if (!$formula) return;

                //         collect(array_keys($this->resultsTable))
                //             ->each(fn($id) => $this->applyFormulaToRow($id, $formula, $data['target_field'], $data['locale'], $data['currency_id']));
                //     }),
                BulkAction::make('applyFormula')
                    ->schema($this->generateModalSchema())
                    ->action(function (Collection $records, array $data) {
                        $formula = $this->availableFormulas()->firstWhere('id', (int) $data['formula_id']);
                        if (!$formula) return;

                        $records->each(fn ($record) => $this->applyFormulaToRow($record['id'], $formula, $data['target_field'], $data['locale'], $data['currency_id']));

                        $this->resetTable();
                    })
                    ->deselectRecordsAfterCompletion()
                    ->label(__('admin.seo.meta_editor.buttons.apply_formula'))
                    ->icon(NavigationItem::MetaEditor->icon())
                    ->color('info'),
            ])
            ->emptyStateHeading(__('admin.seo.meta_editor.helpers.results_table_title'))
            ->emptyStateDescription(__('admin.seo.meta_editor.helpers.results_table_descriptions'))
            ->emptyStateIcon('heroicon-o-clock');
    }

    /**
     * Staging table columns
     * @return \Filament\Tables\Columns\Layout\Grid[]
     */
    protected function resultColumns(): array {
        $languages = once(fn () => Filament::getTenant()->languages()->wherePivot('is_active', true)->pluck('locale'));
        return [
            Grid::make(['lg' => 1, 'xl' => 2])
                ->schema([
                    TextColumn::make('name')
                        ->weight(FontWeight::Bold)
                        ->color(fn($record) => (!empty($record['has_error']) && $record['has_error'] == true) ? 'danger' : (!isset($record['has_error']) ? '' : 'success'))
                        ->columnSpanFull()
                        ->alignCenter()
                        ->size(TextSize::Medium)
                        ->searchable(),

                    Stack::make([
                        TextColumn::make('column_meta_title')
                            ->default(__('admin.common.fields.meta_title'))
                            ->color(Color::Gray)
                            ->size(TextSize::Small),
                        ...collect($languages)->map(fn ($locale) => 
                            TextInputColumn::make("meta_title.{$locale}")
                                ->getStateUsing(fn (array $record) => $record['meta_title'][$locale] ?? '')
                                ->updateStateUsing(fn ($state, array $record) => $this->updateResultField('meta_title', $state, $record, $locale))
                                ->prefix($locale)
                                ->placeholder(__('admin.common.fields.meta_title'))
                                ->suffix(fn ($state): string => strval(str($state)->length()))
                            ),
                    ])->space(2)->columnSpan(['lg' => 1, 'xl' => 1]),

                    Stack::make([
                        TextColumn::make('column_h1')
                            ->default(__('admin.common.fields.h1'))
                            ->color(Color::Gray)
                            ->size(TextSize::Small),
                        ...collect($languages)->map(fn ($locale) => 
                            TextInputColumn::make("h1.{$locale}")
                                ->getStateUsing(fn (array $record) => $record['h1'][$locale] ?? '')
                                ->updateStateUsing(fn ($state, array $record) => $this->updateResultField('h1', $state, $record, $locale))
                                ->prefix($locale)
                                ->placeholder(__('admin.common.fields.h1'))
                                ->suffix(fn ($state): string => strval(str($state)->length()))
                            ),
                    ])->space(2)->columnSpan(['lg' => 1, 'xl' => 1]),

                    Stack::make([
                        TextColumn::make('column_description')
                            ->default(__('admin.common.fields.meta_description'))
                            ->color(Color::Gray)
                            ->size(TextSize::Small),
                        ...collect($languages)->map(fn ($locale) => 
                            TextInputColumn::make("meta_description.{$locale}")
                                ->getStateUsing(fn (array $record) => $record['meta_description'][$locale] ?? '')
                                ->updateStateUsing(fn ($state, array $record) => $this->updateResultField('meta_description', $state, $record, $locale))
                                ->prefix($locale)
                                ->placeholder(__('admin.common.fields.meta_description'))
                                ->suffix(fn ($state): string => strval(str($state)->length()))
                            ),
                    ])->space(2)->columnSpan(['lg' => 1, 'xl' => 2]),
                ]),
        ];
    }

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
     * Formula variables
     */
    protected function buildFormulaVars(array $row, string $locale): array
    {
        return [
            'name'          => $row['name'][$locale] ?? null,
            'store'         => once(fn() => Filament::getTenant()->name),
            'parent'        => $row['parent'][$locale] ?? null,
            // TODO
            'minPrice'      => null,
            'maxPrice'      => null,
            'ratingAvg'     => null,
            'productCount'  => null,
        ];
    }

    /**
     * Update staging array after user input in field 
     * @param string $field
     * @param mixed $state
     * @param array $record
     * @param string $locale
     * @return void
     */
    protected function updateResultField(string $field, mixed $state, array $record, string $locale): void
    {
        $this->resultsTable[$record['id']][$field][$locale] = $state ?? '';
    }

    /**
     * Remove row from staging table
     * @param int|string $id
     * @return void
     */
    protected function removeRecordByKey(int|string $id): void
    {
        unset($this->resultsTable[$id]);
    }

    /**
     * Entity type to separate formulas by type: category, product, etc.
     * @return void
     */
    abstract protected function entityType(): string;

    protected function availableFormulas(): Collection
    {
        $storeId = once(fn() => Filament::getTenant()->id);
        return MetaTagFormula::query()
            ->where('store_id', $storeId)
            ->where('entity_type', $this->entityType())
            ->get();
    }

    /**
     * Applt formula to the input value
     * @param int|string $id
     * @param MetaTagFormula $formula
     * @param mixed $target_field
     * @param mixed $locale
     * @param mixed $currency_id
     * @return void
     */
    protected function applyFormulaToRow(int|string $id, MetaTagFormula $formula, $target_field, $locale, $currency_id): void
    {
        $row = $this->resultsTable[$id] ?? null;
        if ($row === null) {
            return;
        }

        $result = ApplyMetaFormula::apply($formula->formula, $this->buildFormulaVars($row, $locale));

        $this->resultsTable[$id][$target_field][$locale] = $result['text'];
        $this->resultsTable[$id]['has_error'] = ($this->resultsTable[$id]['has_error'] ?? false) || ($result['errors'] !== []);
    }

    /**
     * Save staging table to DB
     * @return void
     */
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

    /**
     * Modal window schema that appears on click on genegate buttons
     * @return \Filament\Forms\Components\Select[]
     */
    protected function generateModalSchema(): array {
        $languages =  once(fn() => Filament::getTenant()->languages()->wherePivot('is_active', true)->get());
        $currencies = once(fn() => Filament::getTenant()->currencies()->wherePivot('is_active', true)->get());

        return [
            Select::make('formula_id')
                ->label(__('admin.seo.meta_editor.fields.formula'))
                ->options(fn () => $this->availableFormulas()->mapWithKeys(fn (MetaTagFormula $f) => [$f->id => $f->formula]))
                ->live()
                ->afterStateUpdated(function (Set $set, $state) {
                    $formula = $this->availableFormulas()->firstWhere('id', (int) $state);
                    $set('target_field', $formula?->target_field);
                    $set('locale', $formula?->locale);
                    $set('currency_id', $formula?->currency_id);
                })
                ->required(),
            Select::make('target_field')
                ->options(AbstractMetaTagsFormulaPage::metaFields())
                ->required(),

            Select::make('locale')
                ->options($languages->mapWithKeys(fn ($lang) => [$lang->locale => $lang->locale]))
                ->required()
                ->label(__('admin.seo.meta_editor.fields.locale')),

            Select::make('currency_id')
                ->options($currencies->mapWithKeys(fn ($c) => [$c->id => $c->iso_code]))
                ->required()
                ->label(__('admin.seo.meta_editor.fields.currency_id')),
        ];
    }

    protected function rowMatchesSearch(array $row, string $search): bool
    {
        $negate = str_starts_with(trim($search), '!');
        $needle = mb_strtolower(trim($negate ? substr(trim($search), 1) : $search));

        if ($needle === '') {
            return true;
        }

        $found = false;

        foreach ($this->translatableFields() as $field) {
            foreach ($row[$field] ?? [] as $value) {
                if (str_contains(mb_strtolower((string) $value), $needle)) {
                    $found = true;
                    break 2;
                }
            }
        }

        return $negate ? ! $found : $found;
    }

    protected function rowHasEmptyField(array $row): bool
    {
        $languages = Filament::getTenant()->languages()->wherePivot('is_active', true)->pluck('locale');
        foreach ($this->translatableFields() as $field) {
            foreach ($languages as $locale) {
                if (blank($row[$field][$locale] ?? null)) {
                    return true;
                }
            }
        }

        return false;
    }
}