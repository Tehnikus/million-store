<?php
namespace App\Filament\Pages;

use App\Models\Store\StoreContact;
use Filament\Schemas\Components\Form;
use App\Filament\Schemas\Tabs\StoreContactForm;
use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Schemas\Components\Actions;
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Filament\Support\AdminMenu\HasCentralizedNavigation;

class StoreContactSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.simple-form';

    public ?array $data = [];

    public function mount(): void
    {
        $store = Filament::getTenant();
        $contact = StoreContact::firstOrNew(['store_id' => $store->id]);
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
                                StoreContactForm::make($language),
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

        StoreContact::updateOrCreate(
            ['store_id' => $store->id],
            $data
        );

        \Filament\Notifications\Notification::make()
            ->title(__('admin.messages.contacts_saved'))
            ->success()
            ->send();
    }

    // Some repeating navigation methods in one place
    use HasCentralizedNavigation;
    protected static function getMenuConfig(): NavigationItem
    {
        return NavigationItem::StoreContacts;
    }

}