<?php

namespace App\Filament\Pages\Tenancy;

use App\Domain\Store\Actions\RegisterStore;
use App\Filament\Schemas\StoreRegistrationWizard;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class StoreWizard extends RegisterTenant
{
    public static function getLabel(): string
    {
        return __('admin.global.store_wizard.navigation_label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make(StoreRegistrationWizard::steps())->columnSpanFull(),
        ]);
    }

    protected function handleRegistration(array $data): Model
    {
        return app(RegisterStore::class)->handle($data);
    }
}