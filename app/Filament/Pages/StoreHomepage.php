<?php

namespace App\Filament\Pages;

use App\Models\Store\StoreSettings;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Actions;
use Filament\Actions\Action;
use App\Filament\Schemas\Tabs\{DescriptionTab, FaqTab, HowToTab, FooterTab};

use App\Filament\Support\AdminMenu\NavigationItem;
use App\Filament\Support\AdminMenu\HasCentralizedNavigation;

class StoreHomepage extends Page
{
    protected string $view = 'filament.pages.simple-form';

    public ?array $data = [];

    public function form(Schema $schema): Schema
    {
        $store      = Filament::getTenant();
        $languages  = $store->activeLanguages();

        return $schema
            ->statePath('data')
            ->components([
                Form::make([
                    Tabs::make('languages')
                        ->schema([
                            ...collect($languages)->map(fn($language) =>
                                Tab::make($language->locale)
                                    ->label("{$language->name}")
                                    ->schema([
                                        Tabs::make("content.{$language->locale}")
                                            ->schema([
                                                DescriptionTab::make($language, ['withSlug' => false]),
                                                FaqTab::make($language),
                                                HowToTab::make($language),
                                                FooterTab::make($language),
                                            ])

                                    ])
                            )
                        ])
                ])
                ->statePath('homepage')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->submit('save')->extraAttributes(['style' => 'min-width: 200px'])->label(__('admin.common.buttons.save')),
                    ]),
                ]),
            ]);            
    }


    public function mount(): void
    {
        $this->form->fill($this->getRecord()?->only('homepage') ?? []);
    }

    public function save(): void
    {
        $store = Filament::getTenant();
        $formData = $this->form->getState();

        $record = StoreSettings::updateOrCreate(
            ['store_id' => $store->id],
            $formData
        );

        $this->form->record($record);

        Notification::make()->success()->title(__('admin.messages.settings_saved'))->send();
    }

    public function getRecord(): ?StoreSettings
    {
        $store = Filament::getTenant();

        return StoreSettings::query()
            ->where('store_id', $store->id)
            ->first();
    }

    // Some repeating navigation methods in one place
    use HasCentralizedNavigation;
    protected static function getMenuConfig(): NavigationItem
    {
        return NavigationItem::StoreHomepage;
    }
}
