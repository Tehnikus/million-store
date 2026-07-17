<?php

namespace App\Filament\Pages;

use App\Filament\Support\NavigationGroup;
use Illuminate\Support\Facades\Storage;

use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Notifications\Notification;

class SeoRobotsEditor extends Page
{
    public ?array $data = [];
    protected string $view = 'filament.pages.simple-form';
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCodeBracket;
    protected static ?int $navigationSort = 6;
    protected static string|\UnitEnum|null $navigationGroup = NavigationGroup::Seo;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make('Robots')
                        ->schema([
                            CodeEditor::make('robots')
                                ->label('Edit File Content')
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
    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.items.robots_editor');
    }

    public function getHeading(): string
    {
        return __('admin.navigation.items.robots_editor');
    }

    public function getTitle(): string
    {
        return __('admin.navigation.items.robots_editor');
    }

}
