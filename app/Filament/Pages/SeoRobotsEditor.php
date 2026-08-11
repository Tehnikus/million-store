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

class SeoRobotsEditor extends Page
{
    public ?array $data = [];
    protected string $view = 'filament.pages.simple-form';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make('Robots')
                        ->schema([
                            CodeEditor::make('robots')
                                ->label(__('admin.common.fields.edit_robots_file'))
                                ->language(Language::Html)
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
        $fileName = "robots/robots_{$store->id}.txt";

        $content = Storage::disk('public')->exists($fileName)
            ? Storage::disk('public')->get($fileName)
            : '';

        $this->form->fill(['robots' => $content]);
    }

    public function save(): void
    {
        $store = Filament::getTenant();
        $formData = $this->form->getState();

        $codeContent = $formData['robots'] ?? '';
        $fileName = "robots/robots_{$store->id}.txt";

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
        return NavigationItem::RobotsEditor;
    }

}
