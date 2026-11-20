@props(['variants', 'search' => '', 'preview' => false])

<div x-data="stockSearch(@js($search), 'X-Stock-Results', @js(! $preview))">
    <form method="GET" action="{{ route('inventory.stock') }}" x-ref="form" x-on:submit.prevent="search()"
        data-search-error="{{ __('inventory.search.error') }}" class="mb-5 flex flex-wrap items-end gap-3">
        @if ($preview)
            <input type="hidden" name="preview" value="1">
        @endif
        <div class="w-full sm:max-w-xs">
            <label for="stock-search" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('inventory.search') }}</label>
            <x-main.inventory.input id="stock-search" type="search" name="q" :value="$search" maxlength="100"
                x-ref="input" x-on:input="schedule()" />
            <x-main.inventory.field-error name="q" />
        </div>
        <x-ui.button type="submit">{{ __('inventory.search.action') }}</x-ui.button>
        <x-ui.button as="a" :href="route('inventory.stock')" variant="outline" :x-cloak="$search === '' ? true : null"
            x-show="hasQuery" x-on:click.prevent="clear()">{{ __('inventory.search.clear') }}</x-ui.button>
        <span x-cloak x-show="loading" role="status" class="self-center text-sm text-gray-600 dark:text-gray-300">
            {{ __('inventory.search.loading') }}
        </span>
    </form>
    <div x-ref="results" aria-live="polite" x-bind:aria-busy="loading.toString()">
        <x-main.inventory.stock-table :variants="$variants" />
    </div>
</div>
