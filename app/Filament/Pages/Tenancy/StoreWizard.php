<?php

namespace App\Filament\Pages\Tenancy;

use App\Domain\Store\Actions\RegisterStore;
use App\Filament\Schemas\StoreRegistrationWizard;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

class StoreWizard extends RegisterTenant
{
    protected Width | string | null $maxContentWidth = Width::Container; // Override login page width as this wizard is considered a login page 

    public static function getLabel(): string
    {
        return __('admin.global.store_wizard.navigation_label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make(StoreRegistrationWizard::steps()),
        ]);
    }

    protected function handleRegistration(array $data): Model
    {
        return app(RegisterStore::class)->handle($data);
    }
}