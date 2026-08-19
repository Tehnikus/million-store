<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}
    </form>

    <div style="display: flex; flex-direction: column; gap: 1rem; padding-top: 0; margin-top: -1rem;">
        @livewire(\App\Filament\Clusters\MetaEditor\Livewire\CategoriesEntitiesTable::class, [], 'categories-entities')
        @livewire(\App\Filament\Clusters\MetaEditor\Livewire\CategoriesResultsTable::class, [], 'categories-results')
    </div>
</x-filament-panels::page>