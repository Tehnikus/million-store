<?php

namespace App\Filament\Pages;

use Illuminate\Support\Facades\Storage;

use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Notifications\Notification;

use App\Filament\Support\AdminMenu\NavigationItem;
use App\Filament\Support\AdminMenu\HasCentralizedNavigation;

class DesignCss extends Page
{
    public ?array $data = [];
    protected string $view = 'filament.pages.simple-form';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make('CSS')
                        ->schema([
                            CodeEditor::make('css')
                                ->label('Edit File Content')
                                ->language(Language::Css)
                                ->hiddenLabel()
                                ->extraAttributes(['style' => 'max-height: 70vh; overflow-y: auto']),
                        ])
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')->submit('save')->extraAttributes(['style' => 'min-width: 200px'])->label(__('admin.common.buttons.save')),
                        ]),
                    ]),
            ])
            ->statePath('data');
        ;
    }

    public function mount(): void
    {
        $store = Filament::getTenant();
        $fileName = "css/custom_{$store->id}.css";

        $content = Storage::disk('public')->exists($fileName)
            ? Storage::disk('public')->get($fileName)
            : '';

        $this->form->fill(['css' => $content]);
    }

    public function save(): void
    {
        $store = Filament::getTenant();
        $formData = $this->form->getState(); // ['css' => '...']

        $codeContent = $formData['css'] ?? '';
        $fileName = "css/custom_{$store->id}.css";

        try {
            Storage::disk('public')->put($fileName, $codeContent);

            Notification::make()
                ->success()
                ->title(__('admin.messages.file_saved'))
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('admin.messages.error_saving_file'))
                ->danger()
                ->send();
        }
    }


    // Some repeating navigation methods in one place
    use HasCentralizedNavigation;
    protected static function getMenuConfig(): NavigationItem
    {
        return NavigationItem::CssEditor;
    }

    public function getSubheading(): string|null
    {
        return __('admin.design.css_editor.subheading');
    }
}
