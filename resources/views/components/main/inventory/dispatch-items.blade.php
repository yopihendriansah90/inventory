@props(['lines'])

<x-common.component-card :title="__('inventory.dispatch.items')">
    <ul class="divide-y divide-gray-200 dark:divide-gray-800">
        @foreach ($lines as $line)
            <li class="flex items-center justify-between gap-4 py-3 text-sm text-gray-900 first:pt-0 last:pb-0 dark:text-white">
                <div class="min-w-0 wrap-anywhere">
                    <p class="font-medium">{{ $line->variant->description() }}</p>
                    <p class="text-gray-600 dark:text-gray-300">{{ $line->variant->displaySku() }}</p>
                </div>
                <span class="shrink-0 rounded-full bg-gray-100 px-3 py-1 font-semibold tabular-nums dark:bg-gray-800">{{ __('inventory.units', ['count' => $line->quantity]) }}</span>
            </li>
        @endforeach
    </ul>
</x-common.component-card>
