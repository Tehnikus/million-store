<?php

namespace App\Filament\Clusters\MetaEditor\Pages;

use App\Filament\Clusters\MetaEditor\MetaEditorCluster;
use App\Models\Seo\MetaTagFormula;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

abstract class AbstractMetaTagsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.clusters.meta-editor.pages.meta-tags-table';
    protected static ?string $cluster = MetaEditorCluster::class;
    public ?array $data = [];

    abstract protected function entityType(): string;
    abstract protected function getEntitiesQuery(): Builder;
    abstract protected function metaFields(): array;

    public function mount(): void
    {
        $this->form->fill(['formulas' => $this->loadFormulas()]);
    }

    public function form(Schema $schema): Schema
    {
        $languages = Filament::getTenant()->languages()->wherePivot('is_active', true)->get();
        $currencies = Filament::getTenant()->currencies()->wherePivot('is_active', true)->get();

        return $schema
            ->statePath('data')
            ->components([
                Repeater::make('formulas')
                    ->hiddenLabel()
                    ->table([
                        TableColumn::make(__('admin.meta_editor.fields.formula')),
                        TableColumn::make(__('admin.meta_editor.fields.target_field'))->width('180px'),
                        TableColumn::make(__('admin.meta_editor.fields.locale'))->width('100px'),
                        TableColumn::make(__('admin.meta_editor.fields.currency'))->width('140px'),
                    ])
                    ->schema([
                        TextInput::make('formula')
                            ->required()
                            ->placeholder(__('admin.meta_editor.helpers.formula_placeholder')),

                        Select::make('target_field')
                            ->options(collect($this->metaFields())->mapWithKeys(
                                fn (string $field) => [$field => __("admin.common.fields.{$field}")]
                            ))
                            ->required(),

                        Select::make('locale')
                            ->options($languages->mapWithKeys(fn ($lang) => [$lang->locale => $lang->locale]))
                            ->required(),

                        Select::make('currency_id')
                            ->options($currencies->mapWithKeys(fn ($c) => [$c->id => $c->iso_code]))
                            ->placeholder('—'),
                    ])
                    ->addActionLabel(__('admin.meta_editor.buttons.add_formula'))
                    ->reorderable(false)
                    ->columnSpanFull()
                    ->defaultItems(0),
            ]);
    }

    public function save(): void
    {
        $storeId = Filament::getTenant()->id;
        $formulas = $this->form->getState()['formulas'] ?? [];

        DB::transaction(function () use ($storeId, $formulas) {
            MetaTagFormula::where('store_id', $storeId)
                ->where('entity_type', $this->entityType())
                ->delete();

            if ($formulas === []) {
                return;
            }

            MetaTagFormula::insert(
                collect($formulas)->map(fn (array $row) => [
                    ...$row,
                    'store_id'    => $storeId,
                    'entity_type' => $this->entityType(),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ])->all()
            );
        });

        Notification::make()->success()->title(__('admin.messages.settings_saved'))->send();
    }

    protected function loadFormulas(): array
    {
        return MetaTagFormula::query()
            ->where('store_id', Filament::getTenant()->id)
            ->where('entity_type', $this->entityType())
            ->get()
            ->map(fn (MetaTagFormula $f) => $f->only(['formula', 'target_field', 'locale', 'currency_id']))
            ->all();
    }

    public function getMetaTags(): array
    {
        return $this->getEntitiesQuery()
            ->get()
            ->map(fn (Model $entity) => $this->toRow($entity))
            ->all();
    }

    protected function toRow(Model $entity): array
    {
        $row = ['id' => $entity->getKey()];

        foreach ($this->metaFields() as $field) {
            $row[$field] = $entity->getTranslations($field);
        }

        return $row;
    }

    protected function resolveEntity(int|string $id): Model
    {
        return $this->getEntitiesQuery()->whereKey($id)->firstOrFail();
    }

    /**
     * @param array<int, array{entity_id: int|string, field: string, locale: string, value: string}> $changes
     */
    public function saveMetaFields(array $changes): void
    {
        DB::transaction(function () use ($changes) {
            collect($changes)
                ->groupBy('entity_id')
                ->each(function ($entityChanges, $entityId) {
                    $entity = $this->resolveEntity($entityId);

                    foreach ($entityChanges as $change) {
                        $entity->setTranslation($change['field'], $change['locale'], $change['value']);
                    }

                    $entity->save();
                });
        });
    }
}