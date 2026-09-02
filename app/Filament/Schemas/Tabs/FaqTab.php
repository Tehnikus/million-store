<?php

namespace App\Filament\Schemas\Tabs;

use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs\Tab;

class FaqTab
{
    public static function make($language): Tab
    {
        return Tab::make("faq.{$language->locale}")
            ->schema(self::schema($language->locale))
            ->label(self::label());
    }

    private static function schema(string $locale): array
    {
        return [
                Repeater::make("faq.{$locale}")
                    ->hiddenLabel()
                    ->table([
                        TableColumn::make(__('admin.common.fields.faq_question'))->width('300px'),
                        TableColumn::make(__('admin.common.fields.faq_answer')),
                    ])
                    ->schema([
                        TextInput::make('question')->placeholder(__('admin.common.fields.faq_question')),
                        Textarea::make('answer')->placeholder(__('admin.common.fields.faq_answer')),
                    ])
                    ->addActionLabel(__('admin.common.buttons.add_faq_row'))
                    ->reorderable(true)
                    ->compact()
                    ->columnSpanFull()
                    ->helperText(__('admin.common.helpers.faq_tab'))
                    ->defaultItems(0),
            ];
    }

    private static function label(): string {
        return __('admin.common.tabs.faq');
    }
}