<?php

namespace App\Filament\Schemas\Tabs;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Schemas\Components\Utilities\Set;

use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;

use App\Domain\Seo\ChecksSlugUniqueness;

class DescriptionTab implements HasTranslatableTab
{
    use ChecksSlugUniqueness;
    public static function schema(string $locale, array $config = []): array
    {
        $withSlug = $config['withSlug'] ?? false;
        $languageId = $config['language_id'] ?? null;
        return [
            TextInput::make("name.{$locale}")
                ->label(__('admin.common.fields.name'))
                ->helperText(__('admin.common.helpers.name'))
                ->columnSpanFull()
                ->live(onBlur: false, debounce: 500)
                ->required()
                ->afterStateUpdated(function (Set $set, $component, $livewire, ?string $state, ?Model $record) use ($languageId) {
                    if ($record?->exists) {
                        return;
                    }

                    $newSlug = Str::slug($state ?? '');
                    $set("slugs.{$languageId}", $newSlug);

                    $slugPath = $component->getContainer()->getStatePath() . ".slugs.{$languageId}";
                    self::validateSlugLive($livewire, $slugPath, $newSlug, $languageId, $record);
                }),
            // URL Slug with condition
            ...($withSlug ? [
                TextInput::make("slugs.{$languageId}")
                    ->label(__('admin.common.fields.slug'))
                    ->helperText(__('admin.common.helpers.slug'))
                    ->columnSpanFull()
                    ->required()
                    ->live(onBlur: false, debounce: 500) // Only check on blur event, so no data sent to server on each keystroke
                    ->afterStateUpdated(function (?string $state, $component, $livewire, ?Model $record) use ($languageId) {
                        self::validateSlugLive($livewire, $component->getStatePath(), $state, $languageId, $record);
                    })
                    ->unique(
                        table: 'slugs',
                        column: 'slug',
                        ignorable: fn() => null,
                        modifyRuleUsing: function (Unique $rule, ?Model $record) use ($languageId) {
                            $rule
                                ->where('store_id', Filament::getTenant()->id)
                                ->where('language_id', $languageId);

                            // Exclude self record from unique check
                            if ($record) {
                                $rule->where(function ($query) use ($record) {
                                    $query->where('sluggable_type', '!=', $record::class)
                                        ->orWhere('sluggable_id', '!=', $record->getKey());
                                });
                            }

                            return $rule;
                        },
                    )
                    ->maxLength(255)
                    ->rules(['alpha_dash:ascii']) // Rule to disallow any characters except alpha-numeric and dashes
                    ->validationMessages(['unique' => __('admin.messages.slug_taken'), 'alpha_dash' => __('admin.seo.slugs.errors.alpha_dash')])
                    ->suffixIcon(function (?string $state, $component, $livewire) {
                        if (blank($state)) {
                            return null;
                        }

                        return $livewire->getErrorBag()->has($component->getStatePath()) ? Heroicon::XCircle : Heroicon::CheckCircle;
                    })
                    ->suffixIconColor(function (?string $state, $component, $livewire) {
                        if (blank($state)) {
                            return null;
                        }

                        return $livewire->getErrorBag()->has($component->getStatePath()) ? 'danger' : 'success';
                    })
            ] : []),

            TextInput::make("h1.{$locale}")
                ->label(__('admin.common.fields.h1'))
                ->helperText(__('admin.common.helpers.h1'))
                ->columnSpanFull(),

            TextInput::make("meta_title.{$locale}")
                ->label(__('admin.common.fields.meta_title'))
                ->helperText(__('admin.common.helpers.meta_title'))
                ->columnSpanFull()
                ->hint(new HtmlString(
                    '<span x-data="{ count: 0, recommended: 60, max: 160, init() { const input = this.$el.closest(\'.fi-fo-field\').querySelector(\'input\'); this.count = input.value.length; input.addEventListener(\'input\', e => this.count = e.target.value.length); } }" x-text="count + \' / \' + max" :style="{ color: (count > max || count < 10) ? \'rgb(220 38 38)\' : (count > recommended ? \'rgb(217 119 6)\' : \'rgb(22 163 74)\') }"></span>'
                ))
                ->columnSpanFull(),

            Textarea::make("meta_description.{$locale}")
                ->label(__('admin.common.fields.meta_description'))
                ->helperText(__('admin.common.helpers.meta_description'))
                ->hint(new HtmlString(
                    '<span x-data="{ count: 0, recommended: 160, max: 250, init() { const input = this.$el.closest(\'.fi-fo-field\').querySelector(\'textarea\'); this.count = input.value.length; input.addEventListener(\'input\', e => this.count = e.target.value.length); } }" x-text="count + \' / \' + max" :style="{ color: (count > max || count < 20) ? \'rgb(220 38 38)\' : (count > recommended ? \'rgb(217 119 6)\' : \'rgb(22 163 74)\') }"></span>'
                ))
                ->columnSpanFull(),

            RichEditor::make("description_short.{$locale}")
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
                    'style' => 'min-height: 15rem; max-height: 50vh; overflow-y: auto;'
                ]),

            RichEditor::make("description_full.{$locale}")
                ->label(__('admin.common.fields.description_full'))
                ->helperText(__('admin.common.helpers.description_full'))
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
                    'style' => 'min-height: 15rem; max-height: 50vh; overflow-y: auto;'
                ]),
        ];
    }

    public static function label(): string
    {
        return __('admin.common.tabs.description');
    }
}