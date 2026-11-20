@props(['kind', 'selected' => null, 'variants' => []])

@php
    $vendor = $kind === 'vendor';
    $inputId = $kind.'-search';
    $selection = $vendor ? 'selectVendor(value)' : 'selectVariant(value)';
    $options = $vendor ? 'vendorOptions' : 'variantOptions';
    $onSaved = "if (\$event.detail.kind === '$kind') { register(\$event.detail.id, \$event.detail.label, \$event.detail.searchLabel); pick(\$event.detail.id) }";
@endphp

<div>
    <label for="{{ $inputId }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
        {{ __($vendor ? 'inventory.ticket.vendor_search' : 'inventory.variant.search') }}
    </label>
    <div class="flex items-start gap-2">
        <div class="min-w-0 grow">
            <x-ui.combobox :default-value="$selected" clear-on-input :x-effect="$selection" :x-on:inventory-option-saved.window="$onSaved"
                x-on:inventory-line-added="clear(false); close()">
                <x-ui.combobox.input :id="$inputId" :placeholder="__($vendor ? 'inventory.ticket.vendor_placeholder' : 'inventory.variant.search_placeholder')"
                    :show-trigger="false" autocomplete="off" />
                <x-ui.combobox.content :aria-label="__($vendor ? 'inventory.ticket.vendor_results' : 'inventory.variant.results')" class="min-w-0">
                    <x-ui.combobox.empty>{{ __($vendor ? 'inventory.ticket.vendor_empty' : 'inventory.variant.no_results') }}</x-ui.combobox.empty>
                    <template x-for="option in {{ $options }}" x-bind:key="option.id">
                        <div role="option" data-slot="combobox-item" x-bind:data-value="option.id"
                            x-init="register(option.id, option.label, option.searchLabel)" x-show="matches(option.searchLabel ?? option.label)"
                            x-bind:aria-selected="value === option.id ? 'true' : 'false'"
                            x-on:pointermove="highlight($el)" x-on:click="pick(option.id)" x-text="option.label"
                            class="cursor-pointer break-words rounded-lg px-3 py-2.5 text-sm text-gray-700 hover:bg-gray-100 data-highlighted:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5 dark:data-highlighted:bg-white/5">
                        </div>
                    </template>
                </x-ui.combobox.content>
            </x-ui.combobox>
        </div>
        <x-main.inventory.variant-dialog :kind="$kind" :variants="$variants" />
    </div>
</div>
