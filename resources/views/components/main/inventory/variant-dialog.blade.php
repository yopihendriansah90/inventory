@props(['variants' => [], 'kind' => 'variant'])

@php
    $vendor = $kind === 'vendor';
    $fields = $vendor ? ['name' => 100] : ['model' => 80, 'color' => 40, 'size' => 12];
    $action = route($vendor ? 'inventory.vendors.store' : 'inventory.variants.store');
@endphp

<x-ui.dialog :data-has-errors="! $vendor && $errors->variant->any() ? 'yes' : null" x-init="if ($el.dataset.hasErrors) show()">
    <x-ui.button x-ref="trigger" x-on:click="show()" aria-haspopup="dialog" x-bind:aria-expanded="open.toString()" class="h-11">
        {{ __('inventory.variant.add_action') }}
    </x-ui.button>

    <x-ui.dialog.content class="max-h-[calc(100dvh-2rem)] overflow-y-auto" aria-modal="true">
        <x-ui.dialog.header>
            <x-ui.dialog.title>{{ __($vendor ? 'inventory.ticket.vendor_add' : 'inventory.variant.add') }}</x-ui.dialog.title>
            <x-ui.dialog.description>{{ __($vendor ? 'inventory.ticket.vendor_details' : 'inventory.variant.details') }}</x-ui.dialog.description>
        </x-ui.dialog.header>
        <form method="POST" action="{{ $action }}" class="space-y-4" data-kind="{{ $kind }}"
            data-notify-validation="{{ __('inventory.notification.invalid') }}"
            x-on:submit.prevent="saveOption($el, $el.dataset.kind, (option) => { close(); $dispatch('inventory-option-saved', option) })">
            @csrf
            @foreach ($fields as $field => $limit)
                <div>
                    <label for="variant-{{ $field }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __($vendor ? 'inventory.ticket.vendor_name' : 'inventory.column.'.$field) }}</label>
                    <x-main.inventory.input :name="$field" :id="'variant-'.$field" :value="is_string(old($field)) ? old($field) : ''"
                        :list="'known-'.$field.'s'" :maxlength="$limit" class="max-sm:text-base" required
                        x-bind:aria-invalid="variantErrors[$el.name]?.length ? 'true' : null"
                        :aria-describedby="'variant-'.$field.'-error'" />
                    @unless ($vendor)
                        <datalist id="known-{{ $field }}s">
                            @foreach ($variants->pluck($field)->unique() as $value)
                                <option value="{{ $value }}"></option>
                            @endforeach
                        </datalist>
                    @endunless
                    <p id="variant-{{ $field }}-error" data-field="{{ $field }}" x-cloak x-show="variantErrors[$el.dataset.field]?.length"
                        x-text="variantErrors[$el.dataset.field]?.[0]" class="mt-1 text-sm text-error-600 dark:text-error-400"></p>
                    <x-main.inventory.field-error :name="$field" bag="variant" />
                </div>
            @endforeach
            <x-ui.dialog.footer>
                <x-ui.dialog.close as-child>
                    <x-ui.button variant="outline" class="min-h-11">{{ __('inventory.variant.cancel') }}</x-ui.button>
                </x-ui.dialog.close>
                <x-ui.button type="submit" class="min-h-11" x-bind:disabled="savingVariant">
                    <span x-show="!savingVariant">{{ __($vendor ? 'inventory.ticket.vendor_save' : 'inventory.variant.save') }}</span>
                    <span x-cloak x-show="savingVariant">{{ __('inventory.variant.saving') }}</span>
                </x-ui.button>
            </x-ui.dialog.footer>
        </form>
    </x-ui.dialog.content>
</x-ui.dialog>
