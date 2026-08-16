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

        <div class="keywordGroupWrapper">
            <div class="keywordGroups"></div>
            <div class="inputGroup">
                <input type="text" class="fi-input" data-add-keyword-group placeholder="{{ __('admin.seo.keywords.js.new_group_placeholder') }}">
                <button type="button" class="fi-btn addKeywordGroupBtn">{{ __('admin.seo.keywords.js.button_add_group') }}</button>
            </div>
        </div>

        <table class="keywordTable"></table>

        <script src="{{ asset('js/admin/nimbleTable.js') }}"></script>
        <script src="{{ asset('js/admin/keywords.js') }}"></script>
    </div>
</x-filament-panels::page>