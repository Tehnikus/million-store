<?php

namespace App\Filament\Pages;

use App\Domain\Store\Actions\RegisterStore;
use App\Filament\Resources\Stores\StoreResource;
use App\Filament\Schemas\StoreRegistrationWizard;
use App\Filament\Support\AdminMenu\HasCentralizedNavigation;
use App\Filament\Support\AdminMenu\NavigationItem;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;

class CreateStoreWizard extends Page
{
    use HasCentralizedNavigation;

    protected static function getMenuConfig(): NavigationItem
    {
        return NavigationItem::StoreWizard;
    }

    protected static bool $isScopedToTenant = false;

    protected string $view = 'filament.pages.simple-form';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Form::make([
                    Wizard::make(StoreRegistrationWizard::steps()),
                ])
                    ->livewireSubmitHandler('create')
                    ->footer([
                        Actions::make([
                            Action::make('create')
                                ->submit('create')
                                ->extraAttributes(['style' => 'min-width: 200px'])
                                ->label(__('admin.global.store_wizard.actions.create')),
                        ]),
                    ]),
            ]);
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $store = app(RegisterStore::class)->handle($data);

        Notification::make()
            ->success()
            ->title(__('admin.messages.store_created'))
            ->send();

        // We'll link directly to a regular StoreResource – it already has a fully-fledged
        // form with repeaters for adding ADDITIONAL languages/currencies/countries,
        // if this one set from the master proves insufficient.
        $this->redirect(StoreResource::getUrl('edit', ['record' => $store]));
    }
}