<div>
    {{ $this->table }}

    <x-filament-actions::modals />
</div>

<style>
    .meta-editor-row-error { background-color: rgb(252 165 165 / 0.1); }
    .dark .meta-editor-row-error { background-color: rgb(153 27 27 / 0.1); }
    .meta-editor-row-ok { background-color: rgb(134 239 172 / 0.1); }
    .dark .meta-editor-row-ok { background-color: rgb(21 128 61 / 0.1); }
</style>