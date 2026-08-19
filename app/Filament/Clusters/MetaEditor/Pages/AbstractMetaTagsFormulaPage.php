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
use Illuminate\Support\HtmlString;

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
                            ->submit('save')
                            ->visible(fn (Get $get): bool => filled($get('formulas')))
                    ])
                    ->alignLeft()
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
                            Action::make('generate')
                                ->label(__('admin.seo.meta_editor.buttons.generate'))
                                ->icon('heroicon-s-puzzle-piece')
                                ->color('info')
                                // ->requiresConfirmation()
                                // ->modalIcon('heroicon-s-puzzle-piece')
                                // ->modalDescription(function(array $arguments, Repeater $component) {
                                //     $rowId = $arguments['item'];                 
                                //     $rowData = $component->getItemState($rowId);
                                //     return new HtmlString(__('admin.seo.meta_editor.messages.generate_confirmation', [
                                //         'target_field'  => __("admin.common.fields.{$rowData['target_field']}"),
                                //         'locale'        => $rowData['locale']
                                //     ]));
                                // })
                                // ->beforeFormValidated(function (Action $action, array $data) {
                                //     // Inspect repeater data in $data
                                //     if (empty($data['items'])) {
                                //         Notification::make()
                                //             ->title('Validation failed')
                                //             ->body('The items repeater cannot be empty.')
                                //             ->danger()
                                //             ->send();

                                //         $action->halt();
                                //     }
                                // })
                                ->action(function (array $arguments, Repeater $component) {
                                    $row = $component->getRawItemState($arguments['item']);
                                    $this->dispatch('meta-editor:apply-formula', formula: $row);
                                }),
                        ])
                        ->addActionLabel(__('admin.seo.meta_editor.buttons.add_formula'))
                        ->reorderable(false)
                        ->cloneable(true)
                        ->columnSpanFull()
                        ->defaultItems(1)
                        ->minItems(1)
                        ,
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