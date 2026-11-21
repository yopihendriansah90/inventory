@props(['tickets', 'warehouse' => false])

<x-common.component-card :title="__($warehouse ? 'inventory.dashboard.warehouse.inbound' : 'inventory.dashboard.qc.recent')">
    <ul @class(['divide-y divide-gray-100 dark:divide-gray-800', 'xl:hidden' => ! $warehouse])>
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
    @unless ($warehouse)
        <div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/5 dark:bg-white/3 xl:block">
            <div class="max-w-full overflow-x-auto">
                <table class="min-w-full" aria-label="{{ __('inventory.dashboard.qc.recent') }}">
                    <thead class="border-b border-gray-100 dark:border-white/5">
                        <tr>
                            @foreach (['ticket', 'vendor', 'date', 'status', 'action'] as $column)
                                <th scope="col" @class([
                                    'px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400',
                                    'text-end' => $column === 'action',
                                    'text-start' => $column !== 'action',
                                ])>{{ __('inventory.column.'.$column) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($tickets as $ticket)
                            <tr class="transition hover:bg-gray-50/50 dark:hover:bg-white/2">
                                <th scope="row" class="px-6 py-4 text-start text-sm font-medium text-gray-800 dark:text-white/90">{{ $ticket->code() }}</th>
                                <td class="wrap-anywhere px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $ticket->vendor->name }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    <time datetime="{{ $ticket->received_on->toDateString() }}">{{ $ticket->received_on->locale(app()->getLocale())->translatedFormat('d M Y') }}</time>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4"><x-main.inventory.receipt-status :status="$ticket->status()" /></td>
                                <td class="px-6 py-4 text-end">
                                    <x-ui.button as="a" :href="route('inventory.ticket.show', $ticket)" variant="outline" size="sm"
                                        :aria-label="__('inventory.ticket.open').' '.$ticket->code()">{{ __('inventory.ticket.open') }}</x-ui.button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-6 text-center text-sm text-gray-600 dark:text-gray-300">{{ __('inventory.ticket.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endunless
    <x-ui.button as="a" :href="$warehouse ? route('inventory.receiving', ['pending' => 1]) : route('inventory.qc')" variant="outline" size="sm">
        {{ __($warehouse ? 'inventory.dashboard.view_queue' : 'inventory.dashboard.all_tickets') }}
    </x-ui.button>
</x-common.component-card>
