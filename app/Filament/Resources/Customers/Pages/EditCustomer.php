<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

// use App\Models\Customer;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            // Anonymize action
            Action::make('anonymize')
                ->label(__('admin.customers.customer.fields.anonymize'))
                ->icon(Heroicon::OutlinedFingerPrint)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('admin.customers.customer.messages.anonymize_title'))
                ->modalDescription(__('admin.customers.customer.messages.anonymize_description'))
                ->modalSubmitActionLabel(__('admin.customers.customer.messages.anonymize_confirm'))
                ->visible(fn () => is_null($this->record->anonymized_at))
                ->action(function () {
                    // Anonymize data
                    $this->record->anonymize();
                    // Fill the form after action is performed
                    $this->fillForm();
                })
               
        ];
    }
}
