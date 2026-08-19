<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}
    </form>

    <div style="display: flex; flex-direction: column; gap: 1rem; padding-top: 0; margin-top: -1rem;">
        @livewire(\App\Filament\Clusters\MetaEditor\Livewire\ProductsEntitiesTable::class, [], 'manufacturers-entities')
        @livewire(\App\Filament\Clusters\MetaEditor\Livewire\ProductsResultsTable::class, [], 'manufacturers-results')
    </div>
</x-filament-panels::page>