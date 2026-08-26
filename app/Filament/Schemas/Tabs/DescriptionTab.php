<?php

namespace App\Filament\Schemas\Tabs;

// use App\Filament\Support\AdminMenu\NavigationItem;
// use App\Models\Seo\MetaTagFormula;
use App\Filament\Schemas\Fields\SlugInput;
use Filament\Actions\Action;
// use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
// use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
// use Filament\Facades\Filament;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Model;

use Filament\Schemas\Components\Component;

class DescriptionTab
{
    public static function make($language, $config): Tab
    {
        return Tab::make("description.{$language->locale}")
            ->schema(self::schema($language, $config))
            ->label(self::label());
    }

    private static function schema($language, array $config = []): array
    {

        $withSlug = $config['withSlug'] ?? false;
        // $sluggableType  = $config['sluggableType']  ?? null;

        // $excludeSelf = function (?Model $owner) use ($sluggableType) {
        //     if (!$owner) {
        //         return null;
        //     }

        //     $type = $sluggableType ?? $owner::class;

        //     return fn($query) => $query->where(function ($q) use ($type, $owner) {
        //         $q->where('sluggable_type', '!=', $type)
        //             ->orWhere('sluggable_id', '!=', $owner->getKey());
        //     });
        // };

        return [
            TextInput::make("name.{$language->locale}")
                ->label(__('admin.common.fields.name'))
                ->helperText(__('admin.common.helpers.name'))
                ->columnSpanFull()
                ->live(onBlur: false, debounce: 500, condition: $withSlug === true)
                ->required()
                ->afterStateUpdated(function (Set $set, Get $get, $component, $livewire, ?string $state, ?Model $record) use ($language, $withSlug) {
                    if (!$withSlug === true) {
                        return;
                    }
                    $slugTouchedPath = "slugs_touched_{$language->id}";
                    if ($get($slugTouchedPath)) {
                        return;
                    }

                    $newSlug = Str::slug($state ?? '', '-', $language->locale);
                    $set("slugs_{$language->id}", $newSlug);

                    $slugPath = $component->getContainer()->getStatePath() . ".slugs_{$language->id}";
                    SlugInput::validateSlugLive($livewire, $slugPath, $newSlug, $language->id, SlugInput::excludeSelfQuery($record));
                }),

                ...($withSlug === true ? SlugInput::makeSlug($language, []) : []),

            Fieldset::make('SEO')
                ->schema([
                    TextInput::make("h1.{$language->locale}")
                        ->label(__('admin.common.fields.h1'))
                        ->helperText(__('admin.common.helpers.h1'))
                        ->columnSpanFull()
                        ->suffixActions([
                            Action::make(__('admin.common.buttons.paste_title'))
                                ->icon('heroicon-o-clipboard-document-check')
                                ->actionJs(<<<JS
                                    \$set('h1.{$language->locale}',  \$get('name.{$language->locale}'))
                                    JS)
                                ->tooltip(__('admin.common.buttons.paste_h1')),
                            // Action::make('Generate')
                            //     ->icon(NavigationItem::MetaEditor->icon())
                            //     ->visible(fn (string $operation): bool => $operation === 'edit')
                            //     ->schema([
                            //         Select::make('formula_id')
                            //             ->label(__('admin.seo.meta_editor.fields.formula'))
                            //             ->options(fn () => MetaTagFormula::where('store_id', Filament::getTenant()->id)->pluck('formula'))
                            //             ->required()
                            //     ])
                            //     ->action(function(?Model $record) {
                            //         dd($record);
                            //     })
                        ]),
        
                    TextInput::make("meta_title.{$language->locale}")
                        ->label(__('admin.common.fields.meta_title'))
                        ->helperText(__('admin.common.helpers.meta_title'))
                        ->columnSpanFull()
                        ->suffixAction(
                            Action::make(__('admin.common.buttons.paste_title'))
                                ->icon('heroicon-o-clipboard-document-check')
                                ->actionJs(<<<JS
                                    \$set('meta_title.{$language->locale}', \$get('h1.{$language->locale}') || \$get('name.{$language->locale}'))
                                    JS)
                                ->tooltip(__('admin.common.buttons.paste_title'))
                        )
                        ->hint(self::characterCountHint(max: 160, recommended: 60, min: 10))
                        ->columnSpanFull(),
        
                    Textarea::make("meta_description.{$language->locale}")
                        ->label(__('admin.common.fields.meta_description'))
                        ->helperText(__('admin.common.helpers.meta_description'))
                        ->hintAction(
                            Action::make(__('admin.common.buttons.paste_description'))
                                ->icon('heroicon-o-clipboard-document-check')
                                ->actionJs(<<<JS
                                    \$set('meta_description.{$language->locale}', ((\$get('meta_title.{$language->locale}') ?? '') + ' ' + (\$state ?? '')).trim())
                                    JS)
                                ->hiddenLabel()
                                ->tooltip(__('admin.common.buttons.paste_description'))
                        )
                        ->hint(self::characterCountHint(max: 250, recommended: 160, min: 20))
                        ->columnSpanFull(),
                ]),

            RichEditor::make("description_short.{$language->locale}")
                ->label(__('admin.common.fields.description_short'))
                ->helperText(__('admin.common.helpers.description_short'))
                ->columnSpanFull()
                ->resizableImages()
                ->toolbarButtons([
                    ['bold', 'italic', 'underline', 'link', 'textColor'],
                    ['h2', 'h3', 'h4'],
                    ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'],
                    ['blockquote', 'bulletList', 'orderedList'],
                    ['table', 'attachFiles'],
                    ['details', 'clearFormatting'],
                    ['undo', 'redo'],
                ])
                ->floatingToolbars([
                    'paragraph' => ['bold', 'italic', 'underline', 'link', 'textColor'],
                    'heading' => ['h1', 'h2', 'h3', 'h4'],
                    'table' => ['tableAddColumnBefore', 'tableAddColumnAfter', 'tableDeleteColumn', 'tableAddRowBefore', 'tableAddRowAfter', 'tableDeleteRow', 'tableMergeCells', 'tableSplitCell', 'tableToggleHeaderRow', 'tableToggleHeaderCell', 'tableDelete',],
                ])
                ->extraInputAttributes([
                    'style' => 'min-height: 10rem; max-height: 30vh; overflow-y: auto;'
                ]),

            RichEditor::make("description_full.{$language->locale}")
                ->label(__('admin.common.fields.description_full'))
                ->helperText(__('admin.common.helpers.description_full'))
                ->columnSpanFull()
                ->resizableImages()
                ->toolbarButtons([
                    ['bold', 'italic', 'underline', 'link', 'textColor'],
                    ['h1', 'h2', 'h3', 'h4'],
                    ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'],
                    ['blockquote', 'bulletList', 'orderedList'],
                    ['table', 'attachFiles'],
                    ['details', 'clearFormatting'],
                    ['undo', 'redo'],
                ])
                ->floatingToolbars([
                    'paragraph' => ['bold', 'italic', 'underline', 'link', 'textColor'],
                    'heading' => ['h1', 'h2', 'h3', 'h4'],
                    'table' => ['tableAddColumnBefore', 'tableAddColumnAfter', 'tableDeleteColumn', 'tableAddRowBefore', 'tableAddRowAfter', 'tableDeleteRow', 'tableMergeCells', 'tableSplitCell', 'tableToggleHeaderRow', 'tableToggleHeaderCell', 'tableDelete',],
                ])
                ->extraInputAttributes([
                    'style' => 'min-height: 20rem; max-height: 70vh; overflow-y: auto;'
                ]),
        ];
    }

    // Count sharacters in input and colored html string with cheracter count
    private static function characterCountHint(int $max, int $recommended, int $min): HtmlString
    {
        return new HtmlString(<<<HTML
            <span
                x-data="{
                    get count() { return (\$state ?? '').length; },
                    get color() {
                        return (this.count > {$max} || this.count < {$min})
                            ? 'rgb(220 38 38)'
                            : (this.count > {$recommended} ? 'rgb(217 119 6)' : 'rgb(22 163 74)');
                    }
                }"
                x-text="count + ' / ' + {$max}"
                :style="{ color: color }"
            ></span>
            HTML);
    }

    private static function label(): string
    {
        return __('admin.common.tabs.description');
    }
}