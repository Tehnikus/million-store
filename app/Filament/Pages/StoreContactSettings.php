<?php
namespace App\Filament\Pages;

use App\Models\Store\StoreSettings;
use Filament\Notifications\Notification;
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
                ->statePath('contacts')
                ->footer([
                    Actions::make([
                        Action::make('save')->submit('save')->extraAttributes(['style' => 'min-width: 200px'])->label(__('admin.common.buttons.save')),
                    ]),
                ]),
            ]);
    }

    public function mount(): void
    {
        $this->form->fill($this->getRecord()?->only('contacts') ?? []);
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
        return NavigationItem::StoreContacts;
    }

}