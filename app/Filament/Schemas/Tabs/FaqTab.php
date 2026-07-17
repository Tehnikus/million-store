<?php

namespace App\Filament\Schemas\Tabs;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class FaqTab implements HasTranslatableTab
{
    public static function schema(string $locale, array $config = []): array
    {
        return [
                Repeater::make("faq.{$locale}")
                    ->hiddenLabel()
                    ->table([
                        TableColumn::make(__('admin.common.fields.faq_question'))->width('300px'),
                        TableColumn::make(__('admin.common.fields.faq_answer')),
                    ])
                    ->schema([
                        TextInput::make('question'),
                        Textarea::make('answer'),
                    ])
                    ->addActionLabel(__('admin.common.buttons.add_faq_row'))
                    ->reorderable(true)
                    ->compact()
                    ->columnSpanFull()
                    ->helperText(__('admin.common.helpers.faq_tab')),
            ];
    }

    public static function label(): string {
        return __('admin.common.tabs.faq');
    }
}