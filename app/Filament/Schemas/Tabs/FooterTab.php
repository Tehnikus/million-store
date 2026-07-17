<?php

namespace App\Filament\Schemas\Tabs;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Callout;

class FooterTab implements HasTranslatableTab
{
    public static function schema(string $locale, array $config = []): array
    {
        return [
            Repeater::make("footer.{$locale}")
                ->hiddenLabel()
                ->table([
                    TableColumn::make(__('admin.common.fields.footer_tab'))->width('300px'),
                    TableColumn::make(__('admin.common.fields.footer_content')),
                ])
                ->schema([
                    TextInput::make('tab'),
                    RichEditor::make('content')
                        ->columnSpanFull()
                        ->resizableImages()
                        ->toolbarButtons([
                            ['bold', 'textColor'],
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
                            'style' => 'min-height: 10rem; max-height: 50vh; overflow-y: auto;'
                        ]),
                ])
                ->addActionLabel(__('admin.common.buttons.add_footer_tab'))
                ->reorderable(true)
                // ->compact()
                ->columnSpanFull()
                ->helperText(__('admin.common.helpers.footer_tab')),

        ];
    }

    public static function label(): string
    {
        return __('admin.common.tabs.footer');
    }
}