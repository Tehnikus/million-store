<?php
namespace App\Filament\Pages;

use App\Models\StoreContact;
use Filament\Schemas\Components\Form;
use App\Filament\Schemas\LanguageTabs;
use App\Filament\Schemas\Tabs\StoreContactForm;
use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use UnitEnum;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Schemas\Components\Actions;
use App\Filament\Support\NavigationGroup;
use Filament\Support\Enums\Alignment;

class StoreContactSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAtSymbol;
    protected static ?int $navigationSort = 3;

    protected static string | \UnitEnum | null $navigationGroup = NavigationGroup::StoreSettings;

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
        $languages = Filament::getTenant()
            ->languages()
            ->wherePivot('is_active', true)
            ->get();

        return $schema
            ->statePath('data')
            ->components([
                Form::make([
                    LanguageTabs::make($languages, [
                        StoreContactForm::class,
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

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.items.store_contacts');
    }

    public function getHeading(): string
    {
        return __('admin.navigation.items.store_contacts');
    }

    public function getTitle(): string
    {
        return __('admin.navigation.items.store_contacts');
    }

}