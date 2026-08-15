<x-filament-panels::page>
    {{-- <div x-data x-init="window.pageWire = $wire"> --}}
    <script>
        document.addEventListener('livewire:init', () => {
            window.pageWire = @this;
            console.log(window.pageWire);
        });

        window.keywordsInterface = {
            lang:           @json(__('admin.seo.keywords.js')),
            languages:      @json($languages),
            keywordGroups:  @json($keywordGroups),
            keywords:       @json($keywords),
        };
    </script>

        <div class="keywordGroups"></div>

        <div class="keywordGroups"></div>
        <div class="flex gap-2 my-2">
            <input type="text" data-add-keyword-group placeholder="{{ __('admin.seo.keywords.js.new_group_placeholder') }}">
            <button type="button" class="addKeywordGroupBtn">{{ __('admin.seo.keywords.js.button_add_group') }}</button>
        </div>

        <table class="keywordTable fi-ta-table"></table>
        <link rel="stylesheet" href="{{ asset('css/admin/keywords.css') }}">
        <script src="{{ asset('js/admin/nimbleTable.js') }}"></script>
        <script src="{{ asset('js/admin/keywords.js') }}"></script>
    {{-- </div> --}}
</x-filament-panels::page>