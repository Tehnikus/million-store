<?php

namespace App\Filament\Pages;

use App\Models\Design\LayoutEditor;
use Filament\Facades\Filament;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Actions\Action;

use App\Filament\Support\AdminMenu\NavigationItem;
use App\Filament\Support\AdminMenu\HasCentralizedNavigation;

class DesignLayoutEditor extends Page
{
    protected string $view = 'filament.pages.simple-form';
    public ?array $data = [];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Tabs::make('layouts')
                        ->schema([
                            Tab::make()
                                ->schema([
                                    Builder::make('layout.content')
                                        ->blocks([
                                            Block::make('heading')
                                                ->schema([
                                                    TextInput::make('content')
                                                        ->label('Heading')
                                                        ->required(),
                                                    Select::make('level')
                                                        ->options([
                                                            'h1' => 'Heading 1',
                                                            'h2' => 'Heading 2',
                                                            'h3' => 'Heading 3',
                                                            'h4' => 'Heading 4',
                                                            'h5' => 'Heading 5',
                                                            'h6' => 'Heading 6',
                                                        ])
                                                        ->required(),
                                                ])
                                                ->columns(2),
                                            Block::make('paragraph')
                                                ->schema([
                                                    Textarea::make('content')
                                                        ->label('Paragraph')
                                                        ->required(),
                                                ]),
                                            Block::make('image')
                                                ->schema([
                                                    FileUpload::make('url')
                                                        ->label('Image')
                                                        ->image()
                                                        ->required(),
                                                    TextInput::make('alt')
                                                        ->label('Alt text')
                                                        ->required(),
                                                ]),
                                        ])
                                ])
                        ])
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')->submit('save')->extraAttributes(['style' => 'min-width: 200px'])->label(__('admin.common.buttons.save')),
                        ]),
                    ]),
            ])
            ->record($this->getRecord())
            ->statePath('data');
    }

    public function mount(): void
    {
        $this->form->fill($this->getRecord()?->toArray() ?? []);
    }

    public function save(): void
    {
        $store = Filament::getTenant();
        $formData = $this->form->getState();
        $formData['store_id'] = $store->id;

        $record = LayoutEditor::updateOrCreate(
            ['store_id' => $formData['store_id']], // store_id condition
            $formData                              // Data to be written
        );

        $this->form->record($record);

        Notification::make()->success()->title(__('admin.messages.settings_saved'))->send();
    }

    public function getRecord(): ?LayoutEditor
    {
        $store = Filament::getTenant();

        return LayoutEditor::query()
            ->where('store_id', $store->id)
            ->first();
    }

    public function getSubheading(): string|null
    {
        return __('admin.design.layout_editor.subheading');
    }


    // Some repeating navigation methods in one place
    use HasCentralizedNavigation;
    protected static function getMenuConfig(): NavigationItem
    {
        return NavigationItem::LayoutEditor;
    }

    //     In blade
//     @foreach ($page->content as $block)
//     @switch($block['type'])
//         @case('heading')
//             @include('components.blocks.heading', ['data' => $block['data']])
//             @break

    //         @case('content')
//             @include('components.blocks.content', ['data' => $block['data']])
//             @break

    //         @default
//             {{-- Handle unknown block types gracefully --}}
//     @endswitch
// @endforeach
}
