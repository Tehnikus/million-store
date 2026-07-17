<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Models\Customer;
use App\Models\CustomerGroup;
use Illuminate\Support\HtmlString;

use Filament\Facades\Filament;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Table;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Support\Icons\Heroicon;

use App\Domain\Customer\Search\CustomerSearch;
use Illuminate\Database\Eloquent\Builder;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('first_name')
                    ->formatStateUsing(function (Customer $customer) {
                        return new HtmlString(
                            '<div><strong>' .
                            '<span class="fi-color fi-color-success fi-text-color-700 dark:fi-text-color-400 fi-badge fi-size-md">' . e($customer->locale) . '</span>' .
                            e($customer->first_name) . ' ' .
                            e($customer->last_name) .
                            '</strong></div>' .
                            '<div><strong>' . e($customer->phone) . '</strong></div>' .
                            '<div><strong>' . e($customer->company_name) . ' ' . e($customer->vat_number) . '</strong></div>'

                        );
                    })
                    ->label(__('admin.customers.customer.fields.customer'))
                    // Search is overriden here: app\Filament\Resources\Customers\Pages\ListCustomers.php => applySearchToTableQuery()
                    // Search logic is here app\Domain\Customer\Search\CustomerSearch.php => apply()
                    ->searchable(),

                TextColumn::make('group.name')
                    ->sortable()
                    ->badge()
                    ->alignment(Alignment::Center)
                    ->label(__('admin.customers.customer.fields.customer_group_id')),
                TextColumn::make('email')
                    ->label('Email address')
                    ->alignment(Alignment::Center)
                    ->label(__('admin.customers.customer.fields.email')),
                IconColumn::make('marketing_opt_in')
                    ->boolean()
                    ->alignment(Alignment::Center)
                    ->label(__('admin.customers.customer.fields.marketing_opt_in')),
                TextColumn::make('gdpr_consent_at')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignment(Alignment::Center)
                    ->label(__('admin.customers.customer.fields.gdpr_consent_at')),

                TextColumn::make('anonymized_at')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignment(Alignment::Center)
                    ->label(__('admin.customers.customer.fields.anonymized_at')),
                TextColumn::make('deleted_at')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignment(Alignment::Center)
                    ->label(__('admin.customers.customer.fields.deleted_at')),
                TextColumn::make('created_at')
                    ->date()
                    ->sortable()
                    ->toggleable()
                    ->alignment(Alignment::Center)
                    ->label(__('admin.customers.customer.fields.created_at')),
                TextColumn::make('updated_at')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignment(Alignment::Center)
                    ->label(__('admin.customers.customer.fields.updated_at')),
                ToggleColumn::make('is_approved')
                    ->alignment(Alignment::Center)
                    ->width('1%')
                    ->label(__('admin.customers.customer.fields.is_approved')),
            ])
            // ->searchable(['first_name', 'last_name', 'phone'])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('customer_group_id')
                    ->label(__('admin.customers.customer.fields.customer_group_id'))
                    ->options(fn() => CustomerGroup::query()
                        ->where('store_id', Filament::getTenant()->id)
                        ->pluck('name', 'id')),

                SelectFilter::make('locale')
                    ->label(__('admin.customers.customer.fields.locale'))
                    ->options(fn() => Filament::getTenant()
                        ->languages()
                        ->wherePivot('is_active', true)
                        ->get()
                        ->pluck('name', 'locale')),
            ])
            ->recordActions([
                ViewAction::make()->iconButton(),
                EditAction::make()->iconButton(),
                RestoreAction::make()->iconButton(),
                // Anonymize action
                Action::make('anonymize')
                    ->label(__('admin.customers.customer.fields.anonymize'))
                    ->icon(Heroicon::OutlinedFingerPrint)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(__('admin.customers.customer.messages.anonymize_title'))
                    ->modalDescription(__('admin.customers.customer.messages.anonymize_description'))
                    ->modalSubmitActionLabel(__('admin.customers.customer.messages.anonymize_confirm'))
                    ->visible(fn (Customer $record) => is_null($record->anonymized_at))
                    ->action(function (Customer $record) {
                        $record->anonymize();
                    })
                    ->iconButton(),
                DeleteAction::make()->iconButton(),
                ForceDeleteAction::make()->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
