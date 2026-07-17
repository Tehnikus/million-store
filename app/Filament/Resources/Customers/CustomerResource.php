<?php

namespace App\Filament\Resources\Customers;

use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Filament\Resources\Customers\Schemas\CustomerForm;
use App\Filament\Resources\Customers\Schemas\CustomerInfolist;
use App\Filament\Resources\Customers\Tables\CustomersTable;
use App\Filament\Support\NavigationGroup;
use App\Models\Customer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Facades\Filament;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static bool $isScopedToTenant = true;

    protected static ?string $recordTitleAttribute = 'full_name';
    
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected static string|\UnitEnum|null $navigationGroup = NavigationGroup::Customers;

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return CustomerForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CustomerInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'view' => ViewCustomer::route('/{record}'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }

    // Show count badge of not approved customers in admin navigation
    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()
            // ->where('store_id', Filament::getTenant()->id) // Duplicates same constrain, not needed
            ->where('is_approved', false)
            ->where('is_anonymized', false)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.customers.customer.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.customers.customer.model_label_singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.customers.customer.navigation_label');
    }

    // Skip global search
    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }
}
