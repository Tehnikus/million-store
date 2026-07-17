<?php

namespace App\Filament\Pages;

use UnitEnum;
use BackedEnum;
use App\Models\StoreHomepageDescription;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Actions;
use Filament\Actions\Action;
use App\Filament\Schemas\LanguageTabs;
use App\Filament\Schemas\Tabs\DescriptionTab;
use App\Filament\Schemas\Tabs\FaqTab;
use App\Filament\Schemas\Tabs\HowToTab;
use App\Filament\Schemas\Tabs\FooterTab;
use App\Filament\Support\NavigationGroup;
use Filament\Support\Enums\Alignment;


class StoreHomepage extends Page
{
    protected string $view = 'filament.pages.simple-form';
    protected static ?string $navigationLabel = 'Homepage';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;
    protected static ?int $navigationSort = 1;
    protected static string | \UnitEnum | null $navigationGroup = NavigationGroup::StoreSettings;

        public ?array $data = [];

    public function mount(): void
    {
        $store = Filament::getTenant();
        $contact = StoreHomepageDescription::firstOrNew(['store_id' => $store->id]);
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
                        DescriptionTab::class,
                        FaqTab::class,
                        HowToTab::class,
                        FooterTab::class,
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

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.items.store_homepage_description');
    }

    public function getHeading(): string
    {
        return __('admin.navigation.items.store_homepage_description');
    }

    public function getTitle(): string
    {
        return __('admin.navigation.items.store_homepage_description');
    }
}
