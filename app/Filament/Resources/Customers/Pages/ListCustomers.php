<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Domain\Customer\Search\CustomerSearch;
use App\Models\Customer\CustomerGroup;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;


class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function applySearchToTableQuery(Builder $query): Builder
    {
        if (filled($search = $this->getTableSearch())) {
            $query->where(fn (Builder $q) => CustomerSearch::looseSearch($q, $search));
        }

        return $query;
    }

    // Uses relation app\Models\Customer\CustomerGroup->customers()
    public function getTabs(): array
    {
        $tabs = ['all' => Tab::make('All')->label(__('admin.customers.customer_groups.fields.all_groups'))->badge($this->getModel()::count())];
 
        $customerGroups = CustomerGroup::orderBy('is_active', 'asc')->orderBy('created_at', 'asc')
            ->withCount('customers')
            ->get();
 
        foreach ($customerGroups as $group) {
            $name = $group->name;
            $slug = str($name)->slug()->toString();
 
            $tabs[$slug] = Tab::make($name)
                ->badge($group->customers_count)
                ->modifyQueryUsing(function ($query) use ($group) {
                    return $query->where('customer_group_id', $group->id);
                });
        }
 
        return $tabs;
    }
}
