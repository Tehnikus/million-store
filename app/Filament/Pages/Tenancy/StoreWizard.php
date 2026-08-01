<?php

namespace App\Filament\Pages\Tenancy;

use App\Domain\Store\Actions\RegisterStore;
use App\Filament\Resources\Stores\StoreResource;
use App\Filament\Schemas\StoreRegistrationWizard;
use App\Models\Global\Store;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

/**
 * Create first store after fresh install
 * Required because Filament does not allow panel access with tenancy enabled without any tenant created
 */
class StoreWizard extends RegisterTenant
{
    protected Width | string | null $maxContentWidth = Width::Container; // Override login page width as this wizard is considered a login page 

    public static function getLabel(): string
    {
        return __('admin.global.store_wizard.navigation_label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Form::make([
                    Wizard::make(StoreRegistrationWizard::steps())
                        ->submitAction(new HtmlString(
                            Blade::render(
                                <<<'BLADE'
                                <x-filament::button type="submit" size="lg">
                                    {{ $label }}
                                </x-filament::button>
                                BLADE,
                                ['label' => __('admin.global.store_wizard.actions.create')],
                            )
                        )),
                ])
                ->livewireSubmitHandler('create')
            ]);
    }

    protected function handleRegistration(array $data): Model
    {
        return app(RegisterStore::class)->handle($data);
    }

    // Remove footer action buttons, Wizard last step submit action is used instead
    protected function getFormActions(): array 
    {
        return []; 
    }

    // // Redirect to stores list after first creation
    protected function getRedirectUrl(): string
    {
        $storeId = Store::query()->latest('id')->value('id');
        return StoreResource::getUrl('edit', ['record' => $storeId, 'tenant' => $storeId]);
    }
}