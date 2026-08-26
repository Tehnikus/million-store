<?php

namespace App\Filament\Schemas\Fields;

use App\Domain\Seo\Actions\SaveSlug;
use App\Models\Seo\Slug;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

class SlugInput
{
    public static function makeSlug($language, array $config) : array {
        
        $nameField         = $config['nameField'] ?? "name.{$language->locale}";
        $generateSlugUsing = $config['generateSlugUsing'] ?? null;

        $slugKey    = "slugs_{$language->id}";
        $touchedKey = "slugs_touched_{$language->id}";
        $robotsKey  = "robots_{$language->id}";

        return [
            Hidden::make($touchedKey)
                ->default(false)
                ->dehydrated(false),

            TextInput::make($slugKey)
                ->afterStateHydrated(function ($set, ?string $state) use ($touchedKey) {
                    if (filled($state)) {
                        $set($touchedKey, true);
                    }
                })
                ->suffixAction(
                    Action::make(__('admin.common.buttons.create_slug'))
                        ->icon('heroicon-o-link')
                        ->action(function (Get $get, Set $set, Component $component, $livewire, ?Model $record) use ($slugKey, $language, $nameField, $generateSlugUsing) {
                            $value = $generateSlugUsing
                                ? $generateSlugUsing($get, $language->locale)
                                : Str::slug($get($nameField) ?? '', '-', $language->locale);

                            $set($slugKey, $value);

                            self::validateSlugLive(
                                $livewire,
                                $component->getStatePath(),
                                $value, // Check new value, not previous
                                $language->id,
                                self::excludeSelfQuery($record),
                            );
                        })
                        ->tooltip(__('admin.common.buttons.create_slug'))
                )
                ->label(__('admin.common.fields.slug'))
                ->helperText(__('admin.common.helpers.slug'))
                ->columnSpanFull()
                ->live(onBlur: false, debounce: 500)
                ->maxLength(255)
                ->rules(['alpha_dash:ascii'])
                ->dehydrated(false)
                ->dehydrateStateUsing(fn () => null)
                ->suffixIcon(function (?string $state, Component $component, $livewire) {
                    if (blank($state)) return null;
                    return $livewire->getErrorBag()->has($component->getStatePath()) ? Heroicon::XCircle : Heroicon::CheckCircle;
                })
                ->suffixIconColor(function (?string $state, Component $component, $livewire) {
                    if (blank($state)) return null;
                    return $livewire->getErrorBag()->has($component->getStatePath()) ? 'danger' : 'success';
                })

                ->afterStateUpdated(function (?string $state, Component $component, $livewire, ?Model $record, Set $set) use ($language, $touchedKey) {
                    self::validateSlugLive(
                        $livewire,
                        $component->getStatePath(),
                        $state,
                        $language->id,
                        self::excludeSelfQuery($record),
                    );

                    $set($touchedKey, true);
                })

                ->unique(
                    table: 'slugs',
                    column: 'slug',
                    ignorable: fn () => null,
                    modifyRuleUsing: function (Unique $rule, ?Model $record) use ($language) {
                        $rule->where('store_id', Filament::getTenant()->id)
                            ->where('language_id', $language->id);

                        if ($record) {
                            $rule->where(fn ($query) => $query
                                ->where('sluggable_type', '!=', $record->getMorphClass())
                                ->orWhere('sluggable_id', '!=', $record->getKey()));
                        }

                        return $rule;
                    },
                )
                ->validationMessages([
                    'unique'     => __('admin.seo.slugs.errors.slug_taken'),
                    'alpha_dash' => __('admin.seo.slugs.errors.alpha_dash'),
                ])

                // Load state and set sibling robots select
                ->loadStateFromRelationshipsUsing(function (?Model $record, Component $component, Set $set) use ($language, $robotsKey) {
                    if (! $record) {
                        return;
                    }

                    $row = Slug::query()
                        ->where('sluggable_type', $record->getMorphClass())
                        ->where('sluggable_id', $record->getKey())
                        ->where('store_id', Filament::getTenant()->id)
                        ->where('language_id', $language->id)
                        ->where('is_active', true)
                        ->first();

                    $component->state($row?->slug);
                    $set($robotsKey, $row?->robots ?? 'index, follow');
                })

                // Save including robots select 
                ->saveRelationshipsUsing(function (Model $record, ?string $state, Get $get) use ($language, $robotsKey) {
                    app(SaveSlug::class)->handle(
                        sluggable: $record,
                        storeId: Filament::getTenant()->id,
                        languageId: $language->id,
                        slugValue: $state,
                        robots: $get($robotsKey),
                    );
                }),

            Select::make($robotsKey)
                ->label(__('admin.seo.slugs.fields.robots'))
                ->options([
                    'index, follow'     => __('admin.seo.slugs.robots.index_follow'),
                    'noindex, follow'   => __('admin.seo.slugs.robots.noindex_follow'),
                    'index, nofollow'   => __('admin.seo.slugs.robots.index_nofollow'),
                    'noindex, nofollow' => __('admin.seo.slugs.robots.noindex_nofollow'),
                ])
                ->default('index, follow')
                ->dehydrated(false)
                ->dehydrateStateUsing(fn () => null)
        ];
    }

    public static function excludeSelfQuery(?Model $record): ?\Closure
    {
        if (! $record) {
            return null;
        }

        return fn ($query) => $query->where(fn ($q) => $q
            ->where('sluggable_type', '!=', $record->getMorphClass())
            ->orWhere('sluggable_id', '!=', $record->getKey()));
    }

    protected static function slugIsTaken(string $slug, int $languageId, ?\Closure $excludeUsing = null): bool
    {
        return Slug::query()
            ->where('store_id', Filament::getTenant()->id)
            ->where('language_id', $languageId)
            ->where('slug', $slug)
            ->when($excludeUsing, fn(Builder $query) => $excludeUsing($query))
            ->exists();
    }

    public static function validateSlugLive(
        $livewire,
        string $path,
        ?string $state,
        string $languageId,
        ?\Closure $excludeUsing,
    ): void {
        $livewire->resetErrorBag($path);

        if (blank($state)) {
            return;
        }

        $validator = Validator::make(
            ['slug' => $state],
            ['slug' => 'alpha_dash:ascii'],
            ['slug.alpha_dash' => __('admin.seo.slugs.errors.alpha_dash')],
        );

        if ($validator->fails()) {
            $livewire->addError($path, $validator->errors()->first('slug'));
            return;
        }

        if (self::slugIsTaken($state, $languageId, $excludeUsing)) {
            $livewire->addError($path, __('admin.seo.slugs.errors.slug_taken'));
        }
    }
}