<x-filament-panels::page>
    <div wire:ignore x-data x-init="window.pageWire = $wire; window.dispatchEvent(new CustomEvent('keywords:wire-ready'))">
        <script>
            window.keywordsInterface = {
                lang:          @json(__('admin.seo.keywords.js')),
                languages:     @json($languages),
                keywordGroups: @json($keywordGroups),
            };
        </script>

        <link rel="stylesheet" href="{{ asset('css/admin/keywords.css') }}">

        <div class="keywordGroups"></div>
        <div class="flex gap-2 my-2">
            <input type="text" data-add-keyword-group placeholder="{{ __('admin.seo.keywords.js.new_group_placeholder') }}">
            <button type="button" class="addKeywordGroupBtn">{{ __('admin.seo.keywords.js.button_add_group') }}</button>
        </div>

        <table class="keywordTable"></table>

        <script src="{{ asset('js/admin/nimbleTable.js') }}"></script>
        <script src="{{ asset('js/admin/keywords.js') }}"></script>
    </div>
</x-filament-panels::page>