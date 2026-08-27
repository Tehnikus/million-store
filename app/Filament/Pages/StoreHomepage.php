<?php

namespace App\Filament\Pages;

use App\Models\Store\StoreHomepageDescription;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Actions;
use Filament\Actions\Action;
use App\Filament\Schemas\Tabs\DescriptionTab;
use App\Filament\Schemas\Tabs\FaqTab;
use App\Filament\Schemas\Tabs\HowToTab;
use App\Filament\Schemas\Tabs\FooterTab;

use App\Filament\Support\AdminMenu\NavigationItem;
use App\Filament\Support\AdminMenu\HasCentralizedNavigation;

class StoreHomepage extends Page
{
    protected string $view = 'filament.pages.simple-form';

    public ?array $data = [];

    public function mount(): void
    {
        $store = Filament::getTenant();
        $contact = StoreHomepageDescription::firstOrNew(['store_id' => $store->id]);
        $this->form->fill($contact->toArray());
    }

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
                                                DescriptionTab::make($language, ['withSlug' => true]),
                                                FaqTab::make($language),
                                                HowToTab::make($language),
                                                FooterTab::make($language),
                                            ])

                                    ])
                            )
                        ])
                ])
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->submit('save')->extraAttributes(['style' => 'min-width: 200px'])->label(__('admin.common.buttons.save')),
                    ]),
                ]),
            ]);            
    }


    public function save(): void
    {
        $store = Filament::getTenant();
        $data = $this->form->getState();
        // dd($data);

        StoreHomepageDescription::updateOrCreate(
            ['store_id' => $store->id],
            $data
        );

        \Filament\Notifications\Notification::make()
            ->title(__('admin.messages.homepage_saved'))
            ->success()
            ->send();
    }

    // Some repeating navigation methods in one place
    use HasCentralizedNavigation;
    protected static function getMenuConfig(): NavigationItem
    {
        return NavigationItem::StoreHomepage;
    }
}
