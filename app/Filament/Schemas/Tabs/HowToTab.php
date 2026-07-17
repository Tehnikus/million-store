<?php

namespace App\Filament\Schemas\Tabs;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs\Tab;

class HowToTab implements HasTranslatableTab
{
   public static function schema(string $locale, array $config = []): array
    {
        return [
                Repeater::make("how_to.{$locale}")
                    ->hiddenLabel()
                    ->table([
                        TableColumn::make(__('admin.common.fields.how_to_step_name'))->width('300px'),
                        TableColumn::make(__('admin.common.fields.how_to_step_text')),
                    ])
                    ->schema([
                        TextInput::make('name'),
                        Textarea::make('text'),
                    ])
                    ->addActionLabel(__('admin.common.buttons.add_how_to_step'))
                    ->reorderable(true)
                    ->compact()
                    ->columnSpanFull()
                    ->helperText(__('admin.common.helpers.how_to_tab')),
            ];
    }

    public static function label(): string {
        return __('admin.common.tabs.how_to');
    }
}