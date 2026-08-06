<?php

namespace App\Filament\Schemas\Tabs;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Fieldset;
use Filament\Support\Icons\Heroicon;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;

use App\Domain\Seo\ChecksSlugUniqueness;
use App\Models\Seo\Slug;
use Filament\Schemas\Components\Component;

class DescriptionTab implements HasTranslatableTab
{
    use ChecksSlugUniqueness;
    public static function schema(string $locale, array $config = []): array
    {
        $withSlug       = $config['withSlug']       ?? false;
        $languageId     = $config['language_id']    ?? null;
        $sluggableType  = $config['sluggableType']  ?? null;

        $excludeSelf = function (?Model $owner) use ($sluggableType) {
            if (!$owner) {
                return null;
            }

            $type = $sluggableType ?? $owner::class;

            return fn($query) => $query->where(function ($q) use ($type, $owner) {
                $q->where('sluggable_type', '!=', $type)
                    ->orWhere('sluggable_id', '!=', $owner->getKey());
            });
        };

        return [
            TextInput::make("name.{$locale}")
                ->label(__('admin.common.fields.name'))
                ->helperText(__('admin.common.helpers.name'))
                ->columnSpanFull()
                ->live(onBlur: false, debounce: 500)
                ->required()
                ->afterStateUpdated(function (Set $set, Get $get, $component, $livewire, ?string $state, ?Model $record) use ($languageId, $locale, $excludeSelf) {
                    // // Skip autofill if record already exists
                    // if ($record?->exists) {
                    //     return;
                    // }
                    $slugTouchedPath = "slugs_touched.{$languageId}";

                    if ($get($slugTouchedPath)) {
                        return;
                    }
                    // Create slur from name
                    $newSlug = Str::slug($state ?? '', '-', $locale);
                    // Fill slug input
                    $set("slugs.{$languageId}", $newSlug);
                    // Validate slug uniqueness and alpha-numeric stuff
                    $slugPath = $component->getContainer()->getStatePath() . ".slugs.{$languageId}";
                    self::validateSlugLive($livewire, $slugPath, $newSlug, $languageId, $excludeSelf($record));
                }),

            // The flag to check if slug already exists thus it will not be autofilled
            ...($withSlug ? [
                \Filament\Forms\Components\Hidden::make("slugs_touched.{$languageId}")
                    ->default(false)
                    ->dehydrated(false),
            ] : []),

            // URL Slug with condition only for forms that need it
            ...($withSlug ? [
                TextInput::make("slugs.{$languageId}")
                    ->label(__('admin.common.fields.slug'))
                    ->helperText(__('admin.common.helpers.slug'))
                    ->columnSpanFull()
                    ->required()
                    ->live(onBlur: false, debounce: 500)
                    
                    ->afterStateUpdated(function (Set $set, ?string $state, $component, $livewire, ?Model $record) use ($languageId, $excludeSelf) {
                        // If user made any input into slug input mark it as touched, so autofill does not change it
                        $set("slugs_touched.{$languageId}", true);

                        self::validateSlugLive($livewire, $component->getStatePath(), $state, $languageId, $excludeSelf($record));
                    })
                    ->afterStateHydrated(function (Set $set, ?string $state) use ($languageId) {
                        // If any slug state present after form values are filled mark it slug input touched, so autofill does not change it
                        if (filled($state)) {
                            $set("slugs_touched.{$languageId}", true);
                        }
                    })

                    ->dehydrated(false)

                    ->unique(
                        table: 'slugs',
                        column: 'slug',
                        ignorable: fn() => null,
                        modifyRuleUsing: function (Unique $rule, ?Model $record) use ($languageId, $sluggableType) {
                            $rule
                                ->where('store_id', Filament::getTenant()->id)
                                ->where('language_id', $languageId);

                            if ($record) {
                                $type = $sluggableType ?? $record::class;

                                $rule->where(function ($query) use ($type, $record) {
                                    $query->where('sluggable_type', '!=', $type)
                                        ->orWhere('sluggable_id', '!=', $record->getKey());
                                });
                            }

                            return $rule;
                        },
                    )

                    ->maxLength(255)
                    ->rules(['alpha_dash:ascii'])
                    ->validationMessages(['unique' => __('admin.seo.slugs.errors.slug_taken'), 'alpha_dash' => __('admin.seo.slugs.errors.alpha_dash')])
                    ->suffixIcon(function (?string $state, $component, $livewire) {
                        if (blank($state)) {return null;}
                        return $livewire->getErrorBag()->has($component->getStatePath()) ? Heroicon::XCircle : Heroicon::CheckCircle;
                    })
                    ->suffixIconColor(function (?string $state, $component, $livewire) {
                        if (blank($state)) {return null;}
                        return $livewire->getErrorBag()->has($component->getStatePath()) ? 'danger' : 'success';
                    })

                    ->loadStateFromRelationshipsUsing(static function (?Model $record, Component $component) use ($languageId) {
                        if (!$record || !method_exists($record, 'currentStoreSlugs')) return;

                        $slugs = $record->currentStoreSlugs->keyBy('language_id');
                        $state = $slugs->get($languageId)?->slug;
                        $component->state($state);
                    })

                    ->saveRelationshipsUsing(static function (Model $record, $state) use ($locale, $languageId) {
                        $storeId = Filament::getTenant()->id;

                        if (filled($state)) {
                            $slugValue = Str::slug($state ?? '', '-', $locale);

                            Slug::updateOrCreate(
                                [
                                    'sluggable_type' => $record->getMorphClass(),
                                    'sluggable_id'   => $record->id,
                                    'store_id'       => $storeId,
                                    'language_id'    => $languageId,
                                ],
                                [
                                    'slug'           => $slugValue,
                                    'is_active'      => true,
                                ]
                            );
                        } else {
                            Slug::where([
                                'sluggable_type' => $record->getMorphClass(),
                                'sluggable_id'   => $record->id,
                                'store_id'       => $storeId,
                                'language_id'    => $languageId,
                            ])->delete();
                        }
                    })

                    ->suffixAction(
                        Action::make(__('admin.common.buttons.create_slug'))
                            ->icon('heroicon-o-link')
                            ->action(function (Get $get, Set $set) use ($languageId, $locale) {
                                $name = $get("name.{$locale}");
                                $newSlug = Str::slug($name ?? '', '-', $locale);
                                $set("slugs.{$languageId}", $newSlug);
                            })
                            ->tooltip(__('admin.common.buttons.create_slug'))
                    )
            ] : []),
            Fieldset::make('SEO')
                ->schema([
                    TextInput::make("h1.{$locale}")
                        ->label(__('admin.common.fields.h1'))
                        ->helperText(__('admin.common.helpers.h1'))
                        ->columnSpanFull()
                        ->suffixAction(
                            Action::make(__('admin.common.buttons.paste_title'))
                                ->icon('heroicon-o-clipboard-document-check')
                                ->actionJs(<<<JS
                                    \$set('h1.{$locale}',  \$get('name.{$locale}'))
                                    JS)
                                ->tooltip(__('admin.common.buttons.paste_h1'))
                        ),
        
                    TextInput::make("meta_title.{$locale}")
                        ->label(__('admin.common.fields.meta_title'))
                        ->helperText(__('admin.common.helpers.meta_title'))
                        ->columnSpanFull()
                        ->suffixAction(
                            Action::make(__('admin.common.buttons.paste_title'))
                                ->icon('heroicon-o-clipboard-document-check')
                                ->actionJs(<<<JS
                                    \$set('meta_title.{$locale}', \$get('h1.{$locale}') || \$get('name.{$locale}'))
                                    JS)
                                ->tooltip(__('admin.common.buttons.paste_title'))
                        )
                        ->hint(self::characterCountHint(max: 160, recommended: 60, min: 10))
                        ->columnSpanFull(),
        
                    Textarea::make("meta_description.{$locale}")
                        ->label(__('admin.common.fields.meta_description'))
                        ->helperText(__('admin.common.helpers.meta_description'))
                        ->hintAction(
                            Action::make(__('admin.common.buttons.paste_description'))
                                ->icon('heroicon-o-clipboard-document-check')
                                ->actionJs(<<<JS
                                    \$set('meta_description.{$locale}', ((\$get('meta_title.{$locale}') ?? '') + ' ' + (\$state ?? '')).trim())
                                    JS)
                                ->hiddenLabel()
                                ->tooltip(__('admin.common.buttons.paste_description'))
                        )
                        ->hint(self::characterCountHint(max: 250, recommended: 160, min: 20))
                        ->columnSpanFull(),

                    Select::make('robots')
                        ->options([
                            'index, follow'     => 'index, follow',
                            'noindex, nofollow' => 'noindex, nofollow',
                            'index, nofollow'   => 'index, nofollow',
                            'noindex, follow'   => 'noindex, follow',
                        ])
                        ->default('index, follow')
                        ->label(__('admin.common.fields.robots'))
                        ->helperText(__('admin.common.helpers.robots'))
                        ->columnSpanFull(),
                ]),

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
                    'style' => 'min-height: 15rem; max-height: 50vh; overflow-y: auto;'
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

    public static function label(): string
    {
        return __('admin.common.tabs.description');
    }
}