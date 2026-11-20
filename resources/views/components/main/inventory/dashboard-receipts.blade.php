@props(['tickets', 'warehouse' => false])

<x-common.component-card :title="__($warehouse ? 'inventory.dashboard.warehouse.inbound' : 'inventory.dashboard.qc.recent')">
    <ul class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($tickets as $ticket)
            <li>
                <a href="{{ route($warehouse ? 'inventory.ticket.receive' : 'inventory.ticket.show', $ticket) }}"
                    class="flex min-h-14 flex-wrap items-center justify-between gap-x-3 gap-y-1 rounded-lg px-2 py-2 text-sm hover:bg-gray-50 focus-visible:outline-brand-500 dark:hover:bg-gray-800">
                    <div class="min-w-0">
                        <p class="font-semibold text-brand-700 dark:text-brand-300">{{ $ticket->code() }}</p>
                        <p class="wrap-anywhere text-gray-600 dark:text-gray-300">{{ $ticket->vendor->name }}</p>
                    </div>
                    @if ($warehouse)
                        <span class="text-gray-700 dark:text-gray-300">{{ __('inventory.warehouse.passed', ['count' => $ticket->lines->sum(fn ($line) => $line->passedQuantity())]) }}</span>
                    @else
                        <x-main.inventory.receipt-status :status="$ticket->status()" />
                    @endif
                </a>
            </li>
        @empty
            <li class="text-sm text-gray-600 dark:text-gray-300">{{ __($warehouse ? 'inventory.dashboard.no_receipts' : 'inventory.ticket.empty') }}</li>
        @endforelse
    </ul>
    <x-ui.button as="a" :href="$warehouse ? route('inventory.receiving', ['pending' => 1]) : route('inventory.qc')" variant="outline" size="sm">
        {{ __($warehouse ? 'inventory.dashboard.view_queue' : 'inventory.dashboard.all_tickets') }}
    </x-ui.button>
</x-common.component-card>
