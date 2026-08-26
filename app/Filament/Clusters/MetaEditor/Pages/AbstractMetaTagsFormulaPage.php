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
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Callout;
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

    public function mount(): void
    {
        $this->form->fill(['formulas' => $this->loadFormulas()]);
    }

    public function form(Schema $schema): Schema
    {
        $languages = once(fn() => Filament::getTenant()->languages()->wherePivot('is_active', true)->get());
        $currencies = once(fn() => Filament::getTenant()->currencies()->wherePivot('is_active', true)->get());

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
                    ])
                    ->alignLeft()
                    ->columnSpanFull(),
                    Repeater::make('formulas')
                        ->hiddenLabel()
                        ->table([
                            TableColumn::make(__('admin.seo.meta_editor.fields.formula'))->markAsRequired(),
                            TableColumn::make(__('admin.seo.meta_editor.fields.target_field'))->width('180px')->markAsRequired(),
                            TableColumn::make(__('admin.seo.meta_editor.fields.locale'))->width('100px')->markAsRequired(),
                            TableColumn::make(__('admin.seo.meta_editor.fields.currency_id'))->width('140px')->markAsRequired(),
                        ])
                        ->schema([
                            TextInput::make('formula')
                                ->required()
                                ->placeholder(__('admin.seo.meta_editor.helpers.formula_placeholder')),

                            Select::make('target_field')
                                ->options(self::metaFields())
                                ->required(),
    
                            Select::make('locale')
                                ->options($languages->mapWithKeys(fn ($lang) => [$lang->locale => $lang->locale]))
                                ->required(),
    
                            Select::make('currency_id')
                                ->options($currencies->mapWithKeys(fn ($c) => [$c->id => $c->iso_code]))
                                ->required(),

                        ])
                        ->addActionLabel(__('admin.seo.meta_editor.buttons.add_formula'))
                        ->addActionAlignment('right')
                        ->reorderable(false)
                        ->cloneable(true)
                        ->columnSpanFull()
                        ->defaultItems(1)
                        ->minItems(1),
                    Section::make(__('admin.seo.meta_editor.helpers.formulas_section'))
                        ->schema([
                            Callout::make()
                                ->description(new HtmlString(__('admin.seo.meta_editor.helpers.formulas_description')))
                                ->info()
                                ->columnSpanFull(),
                        ])
                        ->collapsible()
                        ->collapsed()
                        ->icon('heroicon-o-information-circle')
                        ->iconColor('info')
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->description(__('admin.seo.meta_editor.helpers.formulas_subheading')),
            ]);
    }

    public function save(): void
    {
        $storeId = once(fn() => Filament::getTenant()->id);
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
        $storeId = once(fn() => Filament::getTenant()->id);
        return MetaTagFormula::query()
            ->where('store_id', $storeId)
            ->where('entity_type', $this->entityType())
            ->get()
            ->map(fn (MetaTagFormula $f) => $f->only(['formula', 'target_field', 'locale', 'currency_id']))
            ->all();
    }

    public static function metaFields(): array
    {
        return [
            // 'name'              => __('admin.common.fields.name'),
            'meta_title'        => __('admin.common.fields.meta_title'),
            'h1'                => __('admin.common.fields.h1'),
            'meta_description'  => __('admin.common.fields.meta_description'),
        ];
    }
}