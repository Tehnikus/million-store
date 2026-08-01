<?php

namespace App\Filament\Support\Tables;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class TranslatableColumnState
{

    /**
     * Show fallback locale badge if record locale does not match admin locale
     * @param Model $record
     * @param string $column
     * @param mixed $locale
     * @return HtmlString
     */
    public static function resolve(Model $record, string $column, ?string $locale = null): HtmlString
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