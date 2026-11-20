@props(['tickets'])

<ul class="space-y-3 md:hidden">
    @forelse ($tickets as $ticket)
        <li>
            <a href="{{ route('inventory.dispatch.show', $ticket) }}"
                class="block rounded-xl border border-gray-200 p-4 text-sm text-gray-900 hover:bg-gray-50 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="font-semibold text-brand-600 dark:text-brand-300">{{ $ticket->code() }}</span>
                    <x-main.inventory.dispatch-status :status="$ticket->status" />
                </div>
                <p class="mt-2 wrap-anywhere font-medium">{{ $ticket->recipient_name }}</p>
                <div class="flex flex-wrap justify-between gap-2 text-gray-600 dark:text-gray-300">
                    <span class="wrap-anywhere">{{ $ticket->sales_reference }}</span>
                    <span>{{ $ticket->created_at->translatedFormat('d M Y') }}</span>
                </div>
            </a>
        </li>
    @empty
        <li class="px-4 py-6 text-center text-sm text-gray-600 dark:text-gray-300">{{ __('inventory.dispatch.no_results') }}</li>
    @endforelse
</ul>
<div class="hidden overflow-x-auto md:block">
    <table class="w-full whitespace-nowrap text-start text-sm text-gray-700 dark:text-gray-300">
        <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
            <tr>
                @foreach (['number', 'sales_reference', 'recipient', 'date', 'status'] as $column)
                    <th scope="col" class="px-4 py-3 text-start">{{ __('inventory.dispatch.column.'.$column) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($tickets as $ticket)
                <tr>
                    <th scope="row" class="px-4 py-3 text-start">
                        <a href="{{ route('inventory.dispatch.show', $ticket) }}" class="font-semibold text-brand-600 hover:underline dark:text-brand-300">{{ $ticket->code() }}</a>
                    </th>
                    <td class="px-4 py-3">{{ $ticket->sales_reference }}</td>
                    <td class="px-4 py-3">{{ $ticket->recipient_name }}</td>
                    <td class="px-4 py-3">{{ $ticket->created_at->translatedFormat('d M Y') }}</td>
                    <td class="px-4 py-3"><x-main.inventory.dispatch-status :status="$ticket->status" /></td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-6 text-center">{{ __('inventory.dispatch.no_results') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if ($tickets->hasPages())
    <div class="mt-4">{{ $tickets->links() }}</div>
@endif
