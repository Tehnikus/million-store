<?php

namespace App\Filament\Resources\Slugs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use App\Models\Slug;
use App\Domain\Seo\ChecksSlugUniqueness;

class SlugForm
{
    use ChecksSlugUniqueness;
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('language_id')
                    ->label(__('admin.seo.slugs.fields.language'))
                    ->options(fn() => Filament::getTenant()
                        ->languages()
                        ->wherePivot('is_active', true)
                        ->get()
                        ->mapWithKeys(fn($language) => [$language->id => $language->name]))
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (?string $state, Get $get, $component, $livewire, ?Slug $record, ) {
                        $slugValue = $get('slug');
                        $slugPath = $component->getContainer()->getComponent('slug')->getStatePath();

                        $livewire->resetErrorBag($slugPath);

                        if (blank($slugValue) || blank($state)) {
                            return;
                        }

                        $taken = self::slugIsTaken(
                            $slugValue,
                            (int) $state,
                            $record ? fn($query) => $query->whereKeyNot($record->getKey()) : null,
                        );

                        if ($taken) {
                            $livewire->addError($slugPath, __('admin.seo.slugs.errors.slug_taken'));
                        }
                    }),
                TextInput::make('slug')
                    ->required()
                    ->live(onBlur: false, debounce: 500)
                    ->afterStateUpdated(function (?string $state, $component, $livewire, ?Slug $record, Get $get) {
                        self::validateSlugLive(
                            $livewire,
                            $component->getStatePath(),
                            $state,
                            /* languageId */ (int) $get('language_id'),
                            $record,
                        );
                    })
                    // $path = $component->getStatePath();
                    // $livewire->resetErrorBag($path);

                    // if (blank($state)) {
                    //     return;
                    // }

                    // $validator = Validator::make(
                    //     ['slug' => $state],
                    //     ['slug' => 'alpha_dash'],
                    //     ['slug.alpha_dash' => __('admin.seo.slugs.errors.alpha_dash')],
                    // );

                    // if ($validator->fails()) {
                    //     $livewire->addError($path, $validator->errors()->first('slug'));
                    //     return;
                    // }

                    // if (blank($get('language_id'))) {
                    //     return;
                    // }

                    // $taken = self::slugIsTaken(
                    //     $state,
                    //     (int) $get('language_id'),
                    //     $record ? fn($query) => $query->whereKeyNot($record->getKey()) : null,
                    // );

                    // if ($taken) {
                    //     $livewire->addError($path, __('admin.seo.slugs.errors.slug_taken'));
                    // }
                    // })
                    ->unique(
                        table: 'slugs',
                        column: 'slug',
                        ignorable: fn($record) => $record,
                        modifyRuleUsing: fn($rule, $get) => $rule
                            ->where('store_id', Filament::getTenant()->id)
                            ->where('language_id', $get('language_id')),
                    )
                    ->maxLength(255)
                    ->rules(['alpha_dash:ascii']) // Rule to disallow any characters except alpha-numeric and dashes
                    ->validationMessages(['unique' => __('admin.seo.slugs.errors.slug_taken'), 'alpha_dash' => __('admin.seo.slugs.errors.alpha_dash')])
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
                    ->label(__('admin.seo.slugs.fields.url'))

                ,

                Select::make('sluggable_type')
                    ->options([
                        // List of models that may have SEO URL
                        \App\Models\BlogPost::class => 'Blog Post',
                        \App\Models\BlogTag::class => 'Blog Tag',
                        \App\Models\BlogAuthor::class => 'Blog Author',
                        // \App\Models\Product::class          => 'Product',
                        // \App\Models\Category::class         => 'Category',
                        // \App\Models\Manufacturer::class     => 'Manufacturer',
                        // \App\Models\FilterSeoPage::class    => 'Filter SEO Page',
                        // \App\Models\ProductOption::class    => 'Option',
                        // \App\Models\ProductAttribute::class => 'Attribute',
                        // \App\Models\ProductTag::class       => 'Tag',

                    ])
                    // ->required()
                    ->disabled()            // Remove this when all models are ready
                    ->dehydrated(false)     // Remove this when all models are ready
                    ->helperText(__('admin.seo.slugs.helpers.type')),
                TextInput::make('sluggable_id')
                    ->disabled()            // Remove this when all models are ready
                    ->dehydrated(false)     // Remove this when all models are ready
                    ->label(__('admin.seo.slugs.fields.sluggable_id'))
                    ->helperText(__('admin.seo.slugs.helpers.id')),
                Select::make('redirected_to_id')
                    ->relationship(
                        name: 'redirectedTo',
                        titleAttribute: 'slug',
                        modifyQueryUsing: fn(\Illuminate\Database\Eloquent\Builder $query, \Filament\Schemas\Components\Utilities\Get $get, ?\App\Models\Slug $record) => $query
                            ->where('store_id', Filament::getTenant()->id)
                            ->where('language_id', $get('language_id'))
                            ->when($record, fn($q) => $q->whereKeyNot($record->id)),
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
