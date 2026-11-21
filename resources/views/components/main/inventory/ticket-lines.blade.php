@props(['ticket', 'warehouse' => false, 'printing' => false])

@php
    $lines = $warehouse ? $ticket->lines->filter(fn ($line) => $line->passedQuantity() > 0) : $ticket->lines;
    $columns = $warehouse ? ['sku', 'passed'] : ['sku', 'received', 'passed', 'failed', 'notes'];
    $showQr = ! $warehouse && ! $printing;
    $totals = [
        'received' => $lines->sum('received_quantity'),
        'passed' => $lines->sum(fn ($line) => $line->passedQuantity()),
        'failed' => $lines->sum('failed_quantity'),
    ];
@endphp

@unless ($printing)
    <div class="space-y-3 sm:hidden">
        @foreach ($lines as $line)
            <article class="space-y-3 rounded-xl border border-gray-200 p-3 text-sm dark:border-gray-700">
                <div>
                    <h4 class="wrap-anywhere font-semibold text-gray-900 dark:text-white">{{ $loop->iteration }}. {{ $line->variant->description() }}</h4>
                    <p class="wrap-anywhere text-gray-600 dark:text-gray-300">{{ $line->variant->displaySku() }}</p>
                </div>
                @php
                    $quantities = $warehouse ? ['passed' => $line->passedQuantity()] : [
                        'received' => $line->received_quantity,
                        'passed' => $line->passedQuantity(),
                        'failed' => $line->failed_quantity,
                    ];
                @endphp
                <dl @class(['grid gap-2 text-xs', 'grid-cols-3' => ! $warehouse])>
                    @foreach ($quantities as $label => $quantity)
                        <div>
                            <dt class="text-gray-600 dark:text-gray-300">{{ __('inventory.column.'.$label) }}</dt>
                            <dd class="text-sm font-semibold tabular-nums text-gray-900 dark:text-white">{{ $quantity }}</dd>
                        </div>
                    @endforeach
                </dl>
                @if (! $warehouse && $line->notes)
                    <p class="wrap-anywhere text-gray-700 dark:text-gray-300">
                        <span class="font-semibold">{{ __('inventory.column.notes') }}</span>
                        <span class="block whitespace-pre-line">{{ $line->notes }}</span>
                    </p>
                @endif
                @if ($showQr)
                    <x-main.inventory.qr-dialog :title="__('inventory.variant.qr')" :label="$line->variant->pickerLabel()"
                        :url="route('inventory.variants.qr', $line->variant)" />
                @endif
            </article>
        @endforeach
        <div class="space-y-2 rounded-xl bg-gray-50 p-3 text-sm dark:bg-gray-800">
            <p class="font-semibold text-gray-900 dark:text-white">{{ __('inventory.ticket.total') }}</p>
            <dl @class(['grid gap-2 text-xs', 'grid-cols-3' => ! $warehouse])>
                @foreach ($warehouse ? ['passed' => $totals['passed']] : $totals as $label => $quantity)
                    <div>
                        <dt class="text-gray-600 dark:text-gray-300">{{ __('inventory.column.'.$label) }}</dt>
                        <dd class="text-sm font-semibold tabular-nums text-gray-900 dark:text-white">{{ $quantity }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>
@endunless

<div @class(['hidden overflow-x-auto sm:block' => ! $printing])>
    <table class="w-full text-sm {{ $printing ? 'text-gray-900' : 'text-gray-700 dark:text-gray-300' }}">
        <thead class="border-b border-gray-200 bg-gray-50 {{ $printing ? '' : 'dark:border-gray-700 dark:bg-gray-800' }}">
            <tr>
                <th scope="col" class="w-12 px-3 py-2 text-end">{{ __('inventory.column.number') }}</th>
                @foreach ($columns as $column)
                    <th scope="col" class="px-3 py-2 text-start">{{ __('inventory.column.'.$column) }}</th>
                @endforeach
                @if ($showQr)
                    <th scope="col" class="px-3 py-2 text-start">{{ __('inventory.column.action') }}</th>
                @endif
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 {{ $printing ? '' : 'dark:divide-gray-800' }}">
            @foreach ($lines as $line)
                <tr class="break-inside-avoid">
                    <td class="px-3 py-2 text-end tabular-nums">{{ $loop->iteration }}</td>
                    <th scope="row" class="wrap-anywhere px-3 py-2 text-start font-medium">
                        {{ $line->variant->description() }}
                        <span class="block text-theme-xs font-normal">{{ $line->variant->displaySku() }}</span>
                    </th>
                    @unless ($warehouse)
                        <td class="px-3 py-2 tabular-nums">{{ $line->received_quantity }}</td>
                    @endunless
                    <td class="px-3 py-2 tabular-nums">{{ $line->passedQuantity() }}</td>
                    @unless ($warehouse)
                        <td class="px-3 py-2 tabular-nums">{{ $line->failed_quantity }}</td>
                        <td class="wrap-anywhere px-3 py-2">{{ $line->notes }}</td>
                    @endunless
                    @if ($showQr)
                        <td class="px-3 py-2">
                            <x-main.inventory.qr-dialog :title="__('inventory.variant.qr')" :label="$line->variant->pickerLabel()"
                                :url="route('inventory.variants.qr', $line->variant)" />
                        </td>
                    @endif
                </tr>
            @endforeach
        </tbody>
        <tfoot class="border-t border-gray-200 font-semibold {{ $printing ? '' : 'dark:border-gray-700' }}">
            <tr>
                <th scope="row" colspan="2" class="px-3 py-2 text-start">{{ __('inventory.ticket.total') }}</th>
                @unless ($warehouse)
                    <td class="px-3 py-2 tabular-nums">{{ $totals['received'] }}</td>
                @endunless
                <td class="px-3 py-2 tabular-nums">{{ $totals['passed'] }}</td>
                @unless ($warehouse)
                    <td class="px-3 py-2 tabular-nums">{{ $totals['failed'] }}</td>
                    <td></td>
                @endunless
                @if ($showQr)
                    <td></td>
                @endif
            </tr>
        </tfoot>
    </table>
</div>
