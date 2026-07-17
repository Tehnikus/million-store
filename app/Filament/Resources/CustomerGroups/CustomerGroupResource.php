<?php

namespace App\Filament\Resources\CustomerGroups;

use App\Filament\Resources\CustomerGroups\Pages\CreateCustomerGroup;
use App\Filament\Resources\CustomerGroups\Pages\EditCustomerGroup;
use App\Filament\Resources\CustomerGroups\Pages\ListCustomerGroups;
use App\Filament\Resources\CustomerGroups\Pages\ViewCustomerGroup;
use App\Filament\Resources\CustomerGroups\Schemas\CustomerGroupForm;
use App\Filament\Resources\CustomerGroups\Schemas\CustomerGroupInfolist;
use App\Filament\Resources\CustomerGroups\Tables\CustomerGroupsTable;
use App\Filament\Support\NavigationGroup;
use App\Models\CustomerGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CustomerGroupResource extends Resource
{
    protected static ?string $model = CustomerGroup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = NavigationGroup::Customers;

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return CustomerGroupForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CustomerGroupInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerGroupsTable::configure($table);
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
            'index' => ListCustomerGroups::route('/'),
            'create' => CreateCustomerGroup::route('/create'),
            'view' => ViewCustomerGroup::route('/{record}'),
            'edit' => EditCustomerGroup::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.customers.customer_groups.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.customers.customer_groups.model_label_singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.customers.customer_groups.navigation_label');
    }

    // Skip global search
    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }
}
