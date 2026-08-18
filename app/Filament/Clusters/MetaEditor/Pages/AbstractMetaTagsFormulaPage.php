<?php

namespace App\Filament\Clusters\MetaEditor\Pages;

use App\Filament\Clusters\MetaEditor\MetaEditorCluster;
use App\Models\Seo\MetaTagFormula;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;

abstract class AbstractMetaTagsFormulaPage extends Page
{
    protected static ?string $cluster = MetaEditorCluster::class;

    public ?array $data = [];

    abstract protected function entityType(): string;

    /** @return array<string, string> target_field => label */
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

            Section::make(__('admin.seo.meta_editor.helpers.formulas_heading'))
                ->schema([
                    Actions::make([
                        Action::make('submit')
                            ->label(__('admin.seo.meta_editor.buttons.save_formulas'))
                            ->icon('heroicon-o-check')
                            ->submit('submit')
                            ->visible(fn (Get $get): bool => filled($get('formulas')))
                    ])
                    ->alignRight()
                    ->columnSpanFull(),
                    Repeater::make('formulas')
                        ->hiddenLabel()
                        ->table([
                            TableColumn::make(__('admin.seo.meta_editor.fields.formula')),
                            TableColumn::make(__('admin.seo.meta_editor.fields.target_field'))->width('180px'),
                            TableColumn::make(__('admin.seo.meta_editor.fields.locale'))->width('100px'),
                            TableColumn::make(__('admin.seo.meta_editor.fields.currency'))->width('140px'),
                            TableColumn::make(__('admin.seo.meta_editor.fields.is_active'))->width('80px'),
                        ])
                        ->schema([
                            TextInput::make('formula')
                                ->required()
                                ->placeholder(__('admin.seo.meta_editor.helpers.formula_placeholder')),
    
                            Select::make('target_field')
                                ->options($this->metaFields())
                                ->required(),
    
                            Select::make('locale')
                                ->options($languages->mapWithKeys(fn ($lang) => [$lang->locale => $lang->locale]))
                                ->required(),
    
                            Select::make('currency_id')
                                ->options($currencies->mapWithKeys(fn ($c) => [$c->id => $c->iso_code]))
                                ->placeholder('—'),
    
                            Toggle::make('is_active'),
                        ])
                        ->extraItemActions([
                            Action::make('applyFormula')
                                ->icon('heroicon-s-puzzle-piece')
                                ->color('success')
                                ->action(function (array $arguments, Repeater $component): void {
                                    // Apply formula to $resultsTable rows
                                })
                                ->requiresConfirmation(),
                        ])
                        ->addActionLabel(__('admin.seo.meta_editor.buttons.add_formula'))
                        ->reorderable(false)
                        ->columnSpanFull()
                        ->defaultItems(0)
                        ->live(),
                ])
                ->collapsible()
                ->description(__('admin.seo.meta_editor.helpers.formulas_subheading')),
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
            ->map(fn (MetaTagFormula $f) => $f->only(['formula', 'target_field', 'locale', 'currency_id', 'is_active']))
            ->all();
    }
}