<?php

namespace App\Filament\Support\Columns;

use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

/**
 * Translatable text column with locale badge
 * Can show all strings with locale badges or only fallback locale string with locale badge
 */
class MultilangTextColumn extends TextColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->html()
            ->searchable()
            ->sortable()
        ;
    }

    public function recordColumnSingle(string $column): static
    {
        $this->state(
            fn($record) => $this->fallbackColumn($record, $column)
        );

        return $this;
    }

    public function recordColumnAll(string $column): static
    {
        $this->state(
            fn($record) => $this->allColumns($record, $column)
        );

        return $this;
    }

    /**
     * Display all strings and their locales and mark missing strings with danger locale badge 
     * @param Model $record
     * @param string $column
     * @return HtmlString
     */
    public static function allColumns(Model $record, string $column): HtmlString
    {
        
        $rawState = $record->getTranslations($column);
        $result = '';

        $languages = Filament::getTenant()->languages()->wherePivot('is_active', true)->pluck('locale');

        foreach ($languages as $locale) {
            if (!empty($rawState[$locale])) {
                $result .=
                    '<span class="inline-flex fi-text-color-700 dark:fi-text-color-400 fi-badge fi-size-sm">'
                        . e($locale) .
                    '</span> ' . 
                    e($rawState[$locale]) .
                    '<br>'
                ;
            } else {
                $result .= '<span class="inline-flex fi-color fi-color-danger fi-text-color-700 dark:fi-text-color-400 fi-badge fi-size-sm">' . e($locale) . ' </span> -- <br>';
            }
        }

        return new HtmlString(!empty($result) ? $result : '--');
    }

    /**
     * Display only one fallback string and locale to which sting fell back
     * @param Model $record
     * @param string $column
     * @param mixed $locale
     * @return HtmlString
     */
    public static function fallbackColumn(Model $record, string $column, ?string $locale = null): HtmlString
    {
        $locale ??= app()->getLocale();

        $rawState = $record->getTranslations($column);

        if (filled($rawState[$locale] ?? null)) {
            return new HtmlString(e($rawState[$locale]));
        }

        foreach ($rawState as $translationLocale => $value) {
            if (filled($value)) {
                return new HtmlString(
                    '<span class="inline-flex fi-text-color-700 dark:fi-text-color-400 fi-badge fi-size-sm">'
                        . e($translationLocale) .
                    '</span> ' . e($value)
                );
            }
        }

        return new HtmlString('--');
    }
}