@props(['variants', 'vendors', 'selectedSku'])

@php
    $asText = fn ($value) => is_scalar($value) ? (string) $value : '';
    $sku = strtoupper($selectedSku ?? '');
    $initialVariant = $sku === '' ? null : ($variants->firstWhere('short_sku', $sku) ?? $variants->firstWhere('sku', $sku));
    $initialVendor = filter_var(old('inventory_vendor_id'), FILTER_VALIDATE_INT);
    $oldLines = old('lines', []);
    $lines = collect(is_array($oldLines) ? $oldLines : [])
        ->filter(fn ($line) => is_array($line) && $variants->contains('id', filter_var($line['inventory_variant_id'] ?? null, FILTER_VALIDATE_INT)))
        ->unique(fn ($line) => (int) $line['inventory_variant_id'])
        ->map(fn ($line) => [
            'inventory_variant_id' => (int) $line['inventory_variant_id'],
            'received_quantity' => $asText($line['received_quantity'] ?? ''),
            'failed_quantity' => $asText($line['failed_quantity'] ?? ''),
            'notes' => $asText($line['notes'] ?? ''),
        ])->values()->all();
    $pickerConfig = [
        'variants' => $variants->map(fn ($variant) => [
            'id' => $variant->id,
            'label' => $variant->pickerLabel(),
            'searchLabel' => $variant->searchLabel(),
        ])->values()->all(),
        'vendors' => $vendors->map->option()->values()->all(),
        'selected' => $initialVariant?->id,
        'vendor' => $vendors->contains('id', $initialVendor) ? $initialVendor : null,
        'lines' => $lines,
        'reference' => $asText(old('reference')),
        'receivedOn' => $asText(old('received_on', now('Asia/Jakarta')->toDateString())),
        'step' => $initialVendor && ! $errors->hasAny(['inventory_vendor_id', 'reference', 'received_on']) ? 2 : 1,
        'messages' => [
            'vendor' => __('inventory.ticket.require_vendor'),
            'lines' => __('inventory.ticket.require_lines'),
            'saveError' => __('inventory.ticket.catalog_error'),
            'vendor_added' => __('inventory.notification.vendor_added'),
            'vendor_selected' => __('inventory.notification.vendor_selected'),
            'variant_added' => __('inventory.notification.variant_added'),
            'variant_selected' => __('inventory.notification.variant_selected'),
        ],
    ];
@endphp

<div x-data="inventoryQc(@js($pickerConfig))" class="space-y-4 max-sm:[&_input:not([type=hidden])]:text-base sm:space-y-5">
    <x-main.inventory.ticket-steps :labels="['inventory.ticket.vendor_step', 'inventory.ticket.items_step', 'inventory.ticket.review_step']"
        :compact-labels="['inventory.column.vendor', 'inventory.qc.step_items_short', 'inventory.qc.step_review_short']" />

    @if ($errors->any())
        <ul class="list-disc space-y-1 ps-5 text-sm text-error-700 dark:text-error-300">
            @foreach (array_unique($errors->all()) as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form id="inspection-ticket" method="POST" action="{{ route('inventory.ticket.store') }}"
        data-notify-validation="{{ __('inventory.notification.invalid') }}">
        @csrf
        <input type="hidden" name="inventory_vendor_id" x-bind:value="selectedVendorId ?? ''">
    </form>

    <section x-cloak x-show="step === 1" data-ticket-step="1" class="space-y-4">
        <h3 tabindex="-1" class="text-base font-semibold text-gray-900 xl:scroll-mt-24 dark:text-white">{{ __('inventory.ticket.vendor_step') }}</h3>
        <x-main.inventory.ticket-picker kind="vendor" :selected="$pickerConfig['vendor']" />
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="receipt-reference" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('inventory.column.reference') }}</label>
                <x-main.inventory.input name="reference" id="receipt-reference" form="inspection-ticket" maxlength="80" x-model="reference" required />
            </div>
            <div class="min-w-0">
                <label for="receipt-date" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('inventory.column.date') }}</label>
                <x-main.inventory.input name="received_on" id="receipt-date" form="inspection-ticket" type="date" x-model="receivedOn"
                    class="min-w-0 [&::-webkit-date-and-time-value]:text-start" required />
            </div>
        </div>
    </section>

    <section x-cloak x-show="step === 2" data-ticket-step="2" class="space-y-4">
        <h3 tabindex="-1" class="text-base font-semibold text-gray-900 xl:scroll-mt-24 dark:text-white">{{ __('inventory.ticket.items_step') }}</h3>
        <x-main.inventory.ticket-picker kind="variant" :selected="$initialVariant?->id" :variants="$variants" />
        <div x-show="selectedVariantId !== null">
            <x-ui.button variant="outline" class="h-11 w-full sm:w-auto" x-on:click="addLine()">{{ __('inventory.ticket.add_line') }}</x-ui.button>
        </div>

        <p x-show="!lines.length" class="text-sm text-gray-600 dark:text-gray-300">{{ __('inventory.ticket.no_lines') }}</p>
        <template x-for="(line, index) in lines" x-bind:key="line.inventory_variant_id">
            <div class="space-y-3 rounded-xl border border-gray-200 p-3 dark:border-gray-700 sm:p-4">
                <input type="hidden" form="inspection-ticket" x-bind:name="'lines[' + index + '][inventory_variant_id]'" x-bind:value="line.inventory_variant_id">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p x-text="labelFor(line.inventory_variant_id)" class="wrap-anywhere text-sm font-semibold text-gray-900 dark:text-white"></p>
                    <x-ui.button variant="ghost" size="sm" x-on:click="removeLine(index)">{{ __('inventory.ticket.remove_line') }}</x-ui.button>
                </div>
                <div class="grid grid-cols-2 items-start gap-3 sm:grid-cols-3">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('inventory.column.received') }}
                        <x-main.inventory.input name="received_quantity" class="mt-1.5 min-w-0" form="inspection-ticket" type="number" inputmode="numeric"
                            x-bind:id="'ticket-quantity-' + line.inventory_variant_id" x-bind:name="'lines[' + index + '][received_quantity]'"
                            x-model="line.received_quantity" min="1" max="2147483647" required />
                    </label>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('inventory.column.failed') }}
                        <x-main.inventory.input name="failed_quantity" class="mt-1.5 min-w-0" form="inspection-ticket" type="number" inputmode="numeric"
                            x-bind:name="'lines[' + index + '][failed_quantity]'" x-model="line.failed_quantity" min="0"
                            x-bind:max="line.received_quantity || null" required />
                    </label>
                    <div class="col-span-2 flex items-center justify-between gap-3 rounded-lg bg-success-50 px-3 py-2 text-sm font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400 sm:col-span-1 sm:block sm:bg-transparent sm:p-0 sm:text-gray-700 dark:sm:bg-transparent dark:sm:text-gray-300">
                        {{ __('inventory.column.passed') }}
                        <output x-text="passed(line)" class="block font-semibold tabular-nums sm:mt-1.5 sm:py-2"></output>
                    </div>
                </div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('inventory.qc.notes') }}
                    <textarea name="notes" rows="2" form="inspection-ticket" x-bind:name="'lines[' + index + '][notes]'"
                        x-model="line.notes" maxlength="2000"
                        class="mt-1.5 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-brand-500 focus:outline-hidden focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"></textarea>
                </label>
            </div>
        </template>
    </section>

    <section x-cloak x-show="step === 3" data-ticket-step="3" class="space-y-4">
        <h3 tabindex="-1" class="text-base font-semibold text-gray-900 xl:scroll-mt-24 dark:text-white">{{ __('inventory.ticket.review_step') }}</h3>
        <div class="rounded-xl border border-gray-200 p-4 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-300">
            <p x-text="reviewVendorLabel" class="wrap-anywhere font-semibold text-gray-900 dark:text-white"></p>
            <p class="mt-1 flex flex-wrap gap-x-3"><span x-text="reference" class="min-w-0 wrap-anywhere"></span><span x-text="receivedOn"></span></p>
        </div>
        <div class="space-y-3 sm:hidden">
            <div class="rounded-xl bg-gray-50 p-3 text-sm dark:bg-gray-800">
                <p class="mb-2 font-semibold text-gray-900 dark:text-white">{{ __('inventory.ticket.total') }}</p>
                <dl class="grid grid-cols-3 gap-2">
                    @foreach (['received' => 'received_quantity', 'passed' => 'passed', 'failed' => 'failed_quantity'] as $label => $field)
                        <div>
                            <dt class="text-xs text-gray-600 dark:text-gray-300">{{ __('inventory.column.'.$label) }}</dt>
                            <dd x-text="total(@js($field))" class="font-semibold tabular-nums text-gray-900 dark:text-white"></dd>
                        </div>
                    @endforeach
                </dl>
            </div>
            <template x-for="(line, index) in lines" x-bind:key="line.inventory_variant_id">
                <article class="rounded-xl border border-gray-200 p-3 text-sm dark:border-gray-700">
                    <p x-text="(index + 1) + '. ' + labelFor(line.inventory_variant_id)" class="wrap-anywhere font-semibold text-gray-900 dark:text-white"></p>
                    <dl class="mt-2 grid grid-cols-3 gap-2">
                        @foreach (['received' => 'line.received_quantity', 'passed' => 'passed(line)', 'failed' => 'line.failed_quantity'] as $label => $quantity)
                            <div>
                                <dt class="text-xs text-gray-600 dark:text-gray-300">{{ __('inventory.column.'.$label) }}</dt>
                                <dd x-text="{{ $quantity }}" class="font-semibold tabular-nums text-gray-900 dark:text-white"></dd>
                            </div>
                        @endforeach
                    </dl>
                    <p x-show="line.notes" x-text="line.notes" class="mt-2 wrap-anywhere whitespace-pre-line text-xs text-gray-600 dark:text-gray-300"></p>
                </article>
            </template>
        </div>
        <div class="hidden overflow-x-auto sm:block">
            <table class="w-full text-sm text-gray-700 dark:text-gray-300">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th scope="col" class="w-12 px-3 py-2 text-end">{{ __('inventory.column.number') }}</th>
                        @foreach (['sku', 'received', 'passed', 'failed'] as $column)
                            <th scope="col" class="px-3 py-2 text-start">{{ __('inventory.column.'.$column) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <template x-for="(line, index) in lines" x-bind:key="line.inventory_variant_id">
                        <tr>
                            <td x-text="index + 1" class="px-3 py-2 text-end tabular-nums"></td>
                            <td class="px-3 py-2">
                                <span x-text="labelFor(line.inventory_variant_id)"></span>
                                <span x-show="line.notes" x-text="line.notes" class="block text-theme-xs"></span>
                            </td>
                            <td x-text="line.received_quantity" class="px-3 py-2"></td>
                            <td x-text="passed(line)" class="px-3 py-2"></td>
                            <td x-text="line.failed_quantity" class="px-3 py-2"></td>
                        </tr>
                    </template>
                </tbody>
                <tfoot class="bg-gray-50 font-semibold dark:bg-gray-800">
                    <tr>
                        <th scope="row" colspan="2" class="px-3 py-2 text-start">{{ __('inventory.ticket.total') }}</th>
                        <td x-text="total('received_quantity')" class="px-3 py-2"></td>
                        <td x-text="total('passed')" class="px-3 py-2"></td>
                        <td x-text="total('failed_quantity')" class="px-3 py-2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <p class="text-sm text-gray-700 dark:text-gray-300" x-show="total('passed') === 0">{{ __('inventory.ticket.none_passed') }}</p>
    </section>

    <div class="inventory-form-actions flex gap-2 sm:justify-end">
        <x-ui.button variant="outline" class="min-h-11" x-cloak x-show="step > 1" x-on:click="goTo(step - 1)">{{ __('inventory.ticket.back') }}</x-ui.button>
        <x-ui.button class="min-h-11 max-sm:flex-1" x-cloak x-show="step < 3" x-on:click="next()">{{ __('inventory.ticket.next') }}</x-ui.button>
        <x-ui.button type="submit" form="inspection-ticket" class="min-h-11 max-sm:min-w-0 max-sm:flex-1 max-sm:whitespace-normal"
            x-cloak x-show="step === 3">{{ __('inventory.ticket.finish') }}</x-ui.button>
    </div>
</div>
