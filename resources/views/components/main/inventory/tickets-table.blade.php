@props(['tickets', 'warehouse' => false])

@if ($warehouse)
    <ul class="space-y-3 xl:hidden">
        @forelse ($tickets as $ticket)
            <li>
                <a href="{{ route('inventory.ticket.receive', $ticket) }}"
                    class="block rounded-xl border border-gray-200 px-4 py-3 hover:bg-gray-50 focus-visible:outline-brand-500 dark:border-gray-700 dark:hover:bg-gray-800">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $ticket->code() }}</span>
                        <x-main.inventory.receipt-status :status="$ticket->status()" />
                    </div>
                    <div class="mt-1 flex flex-wrap items-center justify-between gap-2 text-sm text-gray-600 dark:text-gray-300">
                        <span class="wrap-anywhere">{{ $ticket->vendor->name }}</span>
                        <span class="shrink-0">{{ __('inventory.warehouse.passed', ['count' => $ticket->lines->sum(fn ($line) => $line->passedQuantity())]) }}</span>
                    </div>
                </a>
            </li>
        @empty
            <li class="px-4 py-6 text-center text-sm text-gray-600 dark:text-gray-300">{{ __('inventory.ticket.empty') }}</li>
        @endforelse
    </ul>
@endif

<div @class(['overflow-x-auto', 'hidden xl:block' => $warehouse])>
    <table class="w-full whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
        <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
            <tr>
                <th scope="col" class="w-12 px-4 py-3 text-end">{{ __('inventory.column.number') }}</th>
                @foreach ($warehouse ? ['ticket', 'vendor', 'passed', 'status', 'action'] : ['ticket', 'vendor', 'reference', 'date', 'status', 'action'] as $column)
                    <th scope="col" class="px-4 py-3 text-start">{{ __('inventory.column.'.$column) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($tickets as $ticket)
                <tr>
                    <td class="px-4 py-3 text-end tabular-nums">{{ $tickets->firstItem() + $loop->index }}</td>
                    <th scope="row" class="px-4 py-3 text-start font-medium text-gray-900 dark:text-white">{{ $ticket->code() }}</th>
                    <td class="px-4 py-3">{{ $ticket->vendor->name }}</td>
                    @if ($warehouse)
                        <td class="px-4 py-3 tabular-nums">{{ $ticket->lines->sum(fn ($line) => $line->passedQuantity()) }}</td>
                    @else
                        <td class="px-4 py-3">{{ $ticket->reference }}</td>
                        <td class="px-4 py-3">{{ $ticket->received_on->locale(app()->getLocale())->translatedFormat('d M Y') }}</td>
                    @endif
                    <td class="px-4 py-3"><x-main.inventory.receipt-status :status="$ticket->status()" /></td>
                    <td class="px-4 py-3">
                        <x-ui.button as="a" :href="route($warehouse ? 'inventory.ticket.receive' : 'inventory.ticket.show', $ticket)"
                            variant="outline" size="sm">{{ __('inventory.ticket.open') }}</x-ui.button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ $warehouse ? 6 : 7 }}" class="px-4 py-6 text-center">{{ __('inventory.ticket.empty') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if ($tickets->hasPages())
    <div class="mt-4">{{ $tickets->links() }}</div>
@endif
