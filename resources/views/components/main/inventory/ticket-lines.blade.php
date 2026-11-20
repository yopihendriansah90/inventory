@props(['ticket', 'warehouse' => false, 'printing' => false])

@php
    $lines = $warehouse ? $ticket->lines->filter(fn ($line) => $line->passedQuantity() > 0) : $ticket->lines;
    $columns = $warehouse ? ['sku', 'passed'] : ['sku', 'received', 'passed', 'failed', 'notes'];
    $showQr = ! $warehouse && ! $printing;
@endphp

<div class="{{ $printing ? '' : 'overflow-x-auto' }}">
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
                    <td class="px-3 py-2 tabular-nums">{{ $lines->sum('received_quantity') }}</td>
                @endunless
                <td class="px-3 py-2 tabular-nums">{{ $lines->sum(fn ($line) => $line->passedQuantity()) }}</td>
                @unless ($warehouse)
                    <td class="px-3 py-2 tabular-nums">{{ $lines->sum('failed_quantity') }}</td>
                    <td></td>
                @endunless
                @if ($showQr)
                    <td></td>
                @endif
            </tr>
        </tfoot>
    </table>
</div>
