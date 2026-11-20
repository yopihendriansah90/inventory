@props(['variants'])

@php($stockColumns = ['physical' => 'stock', 'reserved' => 'reserved_stock', 'available' => null])

<ul class="space-y-3 md:hidden">
    @forelse ($variants as $variant)
        <li class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
            <p class="wrap-anywhere font-semibold text-gray-900 dark:text-white">{{ $variant->model }}</p>
            <p class="wrap-anywhere text-sm text-gray-600 dark:text-gray-300">{{ $variant->displaySku() }} · {{ $variant->color }} · {{ $variant->size }}</p>
            <dl class="mt-3 grid grid-cols-3 gap-2 border-t border-gray-200 pt-3 text-sm dark:border-gray-700">
                @foreach ($stockColumns as $column => $attribute)
                    <div>
                        <dt class="text-gray-600 dark:text-gray-300">{{ __('inventory.dispatch.stock.'.$column) }}</dt>
                        <dd class="font-semibold tabular-nums text-gray-900 dark:text-white">
                            {{ __('inventory.units', ['count' => $attribute ? $variant->{$attribute} : $variant->availableStock()]) }}
                        </dd>
                    </div>
                @endforeach
            </dl>
        </li>
    @empty
        <li class="px-4 py-6 text-center text-sm text-gray-600 dark:text-gray-300">{{ __('inventory.stock.no_results') }}</li>
    @endforelse
</ul>
<div class="hidden overflow-x-auto md:block">
    <table class="w-full whitespace-nowrap text-start text-sm text-gray-700 dark:text-gray-300">
        <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
            <tr>
                @foreach (['sku', 'model', 'color', 'size'] as $column)
                    <th scope="col" class="px-4 py-3 text-start">{{ __('inventory.column.'.$column) }}</th>
                @endforeach
                @foreach ($stockColumns as $column => $attribute)
                    <th scope="col" class="px-4 py-3 text-end">{{ __('inventory.dispatch.stock.'.$column) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($variants as $variant)
                <tr>
                    <th scope="row" class="px-4 py-3 text-start font-medium text-gray-900 dark:text-white">{{ $variant->displaySku() }}</th>
                    <td class="px-4 py-3">{{ $variant->model }}</td>
                    <td class="px-4 py-3">{{ $variant->color }}</td>
                    <td class="px-4 py-3">{{ $variant->size }}</td>
                    @foreach ($stockColumns as $column => $attribute)
                        <td class="px-4 py-3 text-end tabular-nums {{ $attribute === null ? 'font-semibold text-gray-900 dark:text-white' : '' }}">
                            {{ __('inventory.units', ['count' => $attribute ? $variant->{$attribute} : $variant->availableStock()]) }}
                            @if ($column === 'available' && $variant->availableStock() === 0)
                                <x-ui.badge color="gray" class="ms-2">{{ __('inventory.stock.empty') }}</x-ui.badge>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-6 text-center">{{ __('inventory.stock.no_results') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
