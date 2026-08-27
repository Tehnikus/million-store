<?php

namespace App\Filament\Resources\Slugs\Schemas;

use App\Filament\Schemas\Fields\SlugInput;
use App\Models\Catalog\{Product, Category, Manufacturer, AttributeValue, OptionValue, FacetPage};
use App\Models\Blog\{BlogPost, BlogTag, BlogAuthor};
use App\Models\Seo\Slug;
use App\Models\Store\StoreInfoPage;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\MorphToSelect\Type;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class SlugForm
{
    public static function configure(Schema $schema): Schema
    {
        // Non standart 'name' titleAttribute models
        $titleAttributes = [
            Product::class => 'global_name', // Product has no name, only ProductDescription does
        ];

        return $schema
            ->components([
                Select::make('language_id')
                    ->label(__('admin.seo.slugs.fields.language'))
                    ->options(fn () => Filament::getTenant()
                        ->languages()
                        ->wherePivot('is_active', true)
                        ->get()
                        ->mapWithKeys(fn ($language) => [$language->id => $language->name]))
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (?string $state, Get $get, $component, $livewire, ?Slug $record) {
                        $slugValue = $get('slug');
                        $slugPath = $component->getContainer()->getComponent('slug')->getStatePath();

                        $livewire->resetErrorBag($slugPath);

                        if (blank($slugValue) || blank($state)) {
                            return;
                        }

                        if (SlugInput::slugIsTaken(
                            $slugValue,
                            (int) $state,
                            $record ? fn ($query) => $query->whereKeyNot($record->getKey()) : null,
                        )) {
                            $livewire->addError($slugPath, __('admin.seo.slugs.errors.slug_taken'));
                        }
                    }),

                TextInput::make('slug')
                    ->required()
                    ->live(onBlur: false, debounce: 500)
                    ->afterStateUpdated(function (?string $state, $component, $livewire, ?Slug $record, Get $get) {
                        SlugInput::validateSlugLive(
                            $livewire,
                            $component->getStatePath(),
                            $state,
                            (int) $get('language_id'),
                            $record ? fn ($query) => $query->whereKeyNot($record->getKey()) : null,
                        );
                    })
                    ->unique(
                        table: 'slugs',
                        column: 'slug',
                        ignorable: fn ($record) => $record,
                        modifyRuleUsing: fn ($rule, $get) => $rule
                            ->where('store_id', Filament::getTenant()->id)
                            ->where('language_id', $get('language_id')),
                    )
                    ->maxLength(255)
                    ->rules(['alpha_dash:ascii'])
                    ->validationMessages(['unique' => __('admin.seo.slugs.errors.slug_taken'), 'alpha_dash' => __('admin.seo.slugs.errors.alpha_dash')])
                    ->suffixIcon(fn (?string $state, $component, $livewire) => blank($state) ? null
                        : ($livewire->getErrorBag()->has($component->getStatePath()) ? Heroicon::XCircle : Heroicon::CheckCircle))
                    ->suffixIconColor(fn (?string $state, $component, $livewire) => blank($state) ? null
                        : ($livewire->getErrorBag()->has($component->getStatePath()) ? 'danger' : 'success'))
                    ->label(__('admin.seo.slugs.fields.url')),

                MorphToSelect::make('sluggable')
                    ->types(
                        collect(Relation::morphMap())
                            ->values()
                            ->map(fn (string $class) => Type::make($class)
                                ->titleAttribute($titleAttributes[$class] ?? 'name'))
                            ->all()
                    )
                    ->searchable()
                    ->preload()
                    ->label(__('admin.seo.slugs.fields.entity')),

                Select::make('redirected_to_id')
                    ->relationship(
                        name: 'redirectedTo',
                        titleAttribute: 'slug',
                        modifyQueryUsing: fn (Builder $query, Get $get, ?Slug $record) => $query
                            ->where('store_id', Filament::getTenant()->id)
                            ->where('language_id', $get('language_id'))
                            ->when($record, fn ($q) => $q->whereKeyNot($record->id)),
                    )
                    ->searchable()
                    ->preload()
                    ->optionsLimit(10)
                    ->label(__('admin.seo.slugs.fields.redirect'))
                    ->helperText(__('admin.seo.slugs.helpers.redirect')),

                Toggle::make('is_active')
                    ->required()
                    ->columnSpanFull()
                    ->label(__('admin.common.fields.is_active'))
                    ->helperText(__('admin.seo.slugs.helpers.is_active')),
            ]);
    }
}