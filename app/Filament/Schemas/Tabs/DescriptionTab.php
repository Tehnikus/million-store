<?php

namespace App\Filament\Schemas\Tabs;

use App\Domain\Ai\AiConnection;
use App\Domain\Ai\AiProvider;
use App\Models\Store\StoreSettings;
use App\Filament\Schemas\Fields\SlugInput;
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Models\Seo\MetaTagFormula;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\IconSize;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Model;


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

        return [
            TextInput::make("name.{$language->locale}")
                ->label(__('admin.common.fields.name'))
                ->placeholder(__('admin.common.fields.name'))
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

            RichEditor::make("description_short.{$language->locale}")
                ->label(__('admin.common.fields.description_short'))
                ->placeholder(__('admin.common.fields.description_short'))
                ->helperText(__('admin.common.helpers.description_short'))
                ->hintActions([
                    self::aiModalAction("description_short.{$language->locale}", $language)
                ])
                ->extraInputAttributes([
                    'style' => 'min-height: 10rem; max-height: 30vh; overflow-y: auto;'
                ]),

            RichEditor::make("description_full.{$language->locale}")
                ->label(__('admin.common.fields.description_full'))
                ->placeholder(__('admin.common.fields.description_full'))
                ->helperText(__('admin.common.helpers.description_full'))
                ->hintActions([
                    self::aiModalAction("description_full.{$language->locale}", $language)
                ])
                ->extraInputAttributes([
                    'style' => 'min-height: 20rem; max-height: 70vh; overflow-y: auto;'
                ]),


            TextInput::make("h1.{$language->locale}")
                ->label(__('admin.common.fields.h1'))
                ->placeholder(__('admin.common.fields.h1'))
                ->helperText(__('admin.common.helpers.h1'))
                ->suffixActions([
                    Action::make(__('admin.common.buttons.paste_title'))
                        ->icon('heroicon-o-clipboard-document-check')
                        ->actionJs(<<<JS
                            \$set('h1.{$language->locale}',  \$get('name.{$language->locale}'))
                            JS)
                        ->tooltip(__('admin.common.buttons.paste_h1')),
                    self::metaEditorGenerate(),
                    self::aiModalAction("h1.{$language->locale}", $language)
                ]),

            TextInput::make("meta_title.{$language->locale}")
                ->label(__('admin.common.fields.meta_title'))
                ->placeholder(__('admin.common.fields.meta_title'))
                ->helperText(__('admin.common.helpers.meta_title'))
                ->suffixActions([
                    Action::make(__('admin.common.buttons.paste_title'))
                        ->icon('heroicon-o-clipboard-document-check')
                        ->actionJs(<<<JS
                            \$set('meta_title.{$language->locale}', \$get('h1.{$language->locale}') || \$get('name.{$language->locale}'))
                            JS)
                        ->tooltip(__('admin.common.buttons.paste_title')),
                    self::metaEditorGenerate(),
                    self::aiModalAction("meta_title.{$language->locale}", $language)
                ])
                ->hint(self::characterCountHint(max: 160, recommended: 60, min: 10))
                ->columnSpanFull(),

            Textarea::make("meta_description.{$language->locale}")
                ->label(__('admin.common.fields.meta_description'))
                ->placeholder(__('admin.common.fields.meta_description'))
                ->helperText(__('admin.common.helpers.meta_description'))
                ->hintActions([
                    Action::make(__('admin.common.buttons.paste_description'))
                        ->icon('heroicon-o-clipboard-document-check')
                        ->actionJs(<<<JS
                            \$set('meta_description.{$language->locale}', ((\$get('meta_title.{$language->locale}') ?? '') + ' ' + (\$state ?? '')).trim())
                            JS)
                        ->hiddenLabel()
                        ->tooltip(__('admin.common.buttons.paste_description'))
                        ->iconSize(IconSize::Medium),
                    self::metaEditorGenerate(),
                    self::aiModalAction("meta_title.{$language->locale}", $language)
                ])
                ->hint(self::characterCountHint(max: 250, recommended: 160, min: 20))
                ->columnSpanFull(),
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

    private static function aiModalAction(string $field, $language): Action
    {
        $settings = StoreSettings::query()
            ->where('store_id', Filament::getTenant()->id)
            ->first()?->ai_settings ?? [];

        $promptOptions = collect($settings['prompts'] ?? [])->pluck('name')->all();

        $providerOptions = collect($settings['providers'] ?? [])
            ->mapWithKeys(fn(array $item, $key) => [
                // "Provider - model name" to  distinct several records of the same provider
                $key => implode(' - ', array_filter([
                    AiProvider::tryFrom($item['provider'])?->getLabel() ?? $item['provider'],
                    $item['model'] ?? null,
                ])),
            ])->all();

        // Get default preselected option
        $defaultOf = function (string $group) use ($settings): ?string {
            $key = collect($settings[$group] ?? [])
                ->filter(fn($item) => $item['is_default'] ?? false)
                ->keys()
                ->first();

            return $key === null ? null : (string) $key;
        };

        $defaultProviderKey = $defaultOf('providers');
        $defaultPromptKey = $defaultOf('prompts');

        return Action::make('ai_action')
            ->visible($providerOptions !== [] && $promptOptions !== [])
            ->icon(Heroicon::OutlinedSparkles)
            ->iconSize(IconSize::Medium)
            ->color(Color::Lime)
            ->hiddenLabel()
            ->modalHeading(__('admin.common.buttons.ai_action'))
            ->tooltip(__('admin.common.buttons.ai_action'))
            ->fillForm(function ($schemaComponentState) use ($defaultProviderKey, $defaultPromptKey, $settings) {
                return [
                    'source'     => $schemaComponentState,
                    'provider'   => $defaultProviderKey,
                    'prompt_id'  => $defaultPromptKey,
                    'prompt'     => $settings['prompts'][$defaultPromptKey]['prompt'] ?? null,
                    'has_result' => false,
                ];
            })
            ->schema([
                Hidden::make('source'),
                Hidden::make('has_result'),
                Select::make('provider')
                    ->live(onBlur: true)
                    ->options($providerOptions)
                    ->default($defaultProviderKey)
                    ->required()
                    ->label(__('admin.stores.store_settings.tabs.ai_settings.columns.providers'))
                    ->placeholder(__('admin.stores.store_settings.tabs.ai_settings.columns.providers')),
                Select::make('prompt_id')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, ?string $state) use ($settings) {
                        $set('prompt', $settings['prompts'][$state]['prompt'] ?? null);
                    })
                    ->required()
                    ->options($promptOptions)
                    ->default($defaultPromptKey)
                    ->label(__('admin.stores.store_settings.tabs.ai_settings.columns.prompts'))
                    ->placeholder(__('admin.stores.store_settings.tabs.ai_settings.columns.prompts')),
                FusedGroup::make([
                    // Extra prompt directions
                    Textarea::make('prompt')
                        ->live(onBlur: true)
                        ->rows(5)
                        ->required()
                        ->default($settings['prompts'][$defaultPromptKey]['prompt'] ?? null)
                        ->label(__('admin.stores.store_settings.tabs.ai_settings.labels.prompt_text'))
                        ->placeholder(__('admin.stores.store_settings.tabs.ai_settings.labels.prompt_text')),
                    Textarea::make('extra')
                        ->rows(3)
                        ->label(__('admin.stores.store_settings.tabs.ai_settings.labels.prompt_extra'))
                        ->placeholder(__('admin.stores.store_settings.tabs.ai_settings.labels.prompt_extra')),
                ])
                    ->label(__('admin.stores.store_settings.tabs.ai_settings.labels.prompt')),

                Actions::make([
                    Action::make('generate')
                        ->label(__('admin.common.buttons.ai_generate'))
                        ->icon(Heroicon::OutlinedSparkles)
                        ->color(Color::Lime)
                        ->disabled(fn(Get $schemaGet): bool => blank($schemaGet('provider')) || blank($schemaGet('prompt')))
                        ->tooltip(fn(Get $schemaGet): ?string => (blank($schemaGet('provider')) || blank($schemaGet('prompt')))
                            ? __('admin.messages.ai_fill_required_fields')
                            : null)
                        ->action(function (Get $schemaGet, Set $schemaSet) use ($settings) {
                            $prompt = trim((string) $schemaGet('prompt'));
                            $item   = $settings['providers'][$schemaGet('provider')] ?? null;

                            if (blank($prompt) || $item === null) {
                                return;
                            }

                            set_time_limit(120);

                            try {
                                $text = AiProvider::fromState($item['provider'])
                                    ->client()
                                    ->generate(AiConnection::fromSettings($item), $prompt);
                            } catch (\Throwable $e) {
                                report($e);
                                Notification::make()->danger()->title(__('admin.messages.ai_provider_error'))->body($e->getMessage())->send();
                                return;
                            }

                            $schemaSet('result', Str::markdown($text, ['html_input' => 'strip', 'allow_unsafe_links' => false]));
                            $schemaSet('has_result', true);
                        }),
                ]),

                RichEditor::make('result')
                    ->label(__('admin.common.fields.ai_result'))
                    ->required()
            ])

            ->modalSubmitAction(fn (Action $action, Get $schemaGet) => $action
                ->label(__('admin.common.buttons.ai_action_replace'))
                ->icon(Heroicon::OutlinedArrowPathRoundedSquare)
                ->color('primary')
                ->disabled(fn (): bool => ! $schemaGet('has_result'))
            )

            ->extraModalFooterActions(fn (Action $action): array => [
                $action->makeModalSubmitAction('append', arguments: ['append' => true])
                    ->label(__('admin.common.buttons.ai_action_append'))
                    ->icon(Heroicon::OutlinedForward)
                    ->color('primary')
                    ->disabled(fn (Get $schemaGet): bool => ! $schemaGet('has_result')),
            ])
            ->action(function (array $data, array $arguments, Set $schemaSet, Component $schemaComponent, $schemaComponentState) {
                $html = ($arguments['append'] ?? false)
                    ? (string) $schemaComponentState . $data['result']
                    : $data['result'];

                $schemaSet($schemaComponent->getStatePath(false), $html);
            });
    }

    private static function metaEditorGenerate(): Action
    {
        return Action::make('Generate')
            ->icon(NavigationItem::MetaEditor->icon())->hiddenLabel()->iconSize(IconSize::Medium)
            ->color('info')
            ->tooltip(__('admin.common.buttons.meta_editor'))
            // ->visible(fn (string $operation): bool => $operation === 'edit')
            ->schema([
                Select::make('formula_id')
                    ->label(__('admin.seo.meta_editor.fields.formula'))
                    ->options(fn() => MetaTagFormula::where('store_id', Filament::getTenant()->id)->pluck('formula'))
                    ->required()
                    ->native(false)
                    ->searchable()
                    ->preload()
            ])
            ->action(function (?Model $record) {
                dd($record);
            });
    }

    private static function label(): string
    {
        return __('admin.common.tabs.description');
    }
}