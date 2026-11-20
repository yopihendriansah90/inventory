@props(['tickets', 'warehouse' => false])

<x-common.component-card :title="__($warehouse ? 'inventory.dashboard.warehouse.outbound' : 'inventory.dashboard.cs.recent')">
    <ul class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($tickets as $ticket)
            <li>
                <a href="{{ route('inventory.dispatch.show', $ticket) }}"
                    class="flex min-h-14 flex-wrap items-center justify-between gap-x-3 gap-y-1 rounded-lg px-2 py-2 text-sm hover:bg-gray-50 focus-visible:outline-brand-500 dark:hover:bg-gray-800">
                    <div class="min-w-0">
                        <p class="font-semibold text-brand-700 dark:text-brand-300">{{ $ticket->code() }}</p>
                        <p class="wrap-anywhere text-gray-600 dark:text-gray-300">{{ $warehouse ? $ticket->sales_reference : $ticket->recipient_name }}</p>
                    </div>
                    <x-main.inventory.dispatch-status :status="$ticket->status" />
                </a>
            </li>
        @empty
            <li class="text-sm text-gray-600 dark:text-gray-300">{{ __($warehouse ? 'inventory.dashboard.no_dispatches' : 'inventory.dispatch.no_results') }}</li>
        @endforelse
    </ul>
    <x-ui.button as="a" :href="route('inventory.dispatch.index')" variant="outline" size="sm">{{ __('inventory.dashboard.all_dispatches') }}</x-ui.button>
</x-common.component-card>
