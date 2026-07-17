<x-filament-panels::page>
    <form wire:submit="save" class="fi-sc-form">
        {{ $this->form }}

        <div class="fi-ac fi-align-start">
            <x-filament::button type="submit">
                {{ __('admin.common.buttons.save') }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>