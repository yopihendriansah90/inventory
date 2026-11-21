@props(['days'])

@php
    $passed = __('inventory.dashboard.qc.chart_passed');
    $failed = __('inventory.dashboard.qc.chart_failed');
    $config = [
        'locale' => app()->getLocale(),
        'passed' => $passed,
        'failed' => $failed,
        'days' => array_map(fn ($day) => $day + ['url' => route('inventory.qc', ['date' => $day['date']])], $days),
    ];
@endphp

<x-common.component-card :title="__('inventory.dashboard.qc.chart_title')" :desc="__('inventory.dashboard.qc.chart_description')">
    @unless (collect($days)->contains(fn ($day) => $day['passed'] > 0 || $day['failed'] > 0))
        <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('inventory.dashboard.qc.chart_empty') }}</p>
    @endunless
    <div class="min-h-[260px]" data-dashboard-chart="qc-inspection" data-config="{{ json_encode($config, JSON_THROW_ON_ERROR) }}"></div>
    <details class="rounded-lg border border-gray-200 p-3 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-300">
        <summary class="cursor-pointer font-medium focus-visible:outline-brand-500">{{ __('inventory.dashboard.qc.chart_details') }}</summary>
        <table class="mt-3 w-full">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th scope="col" class="py-2 text-start font-medium">{{ __('inventory.column.date') }}</th>
                    <th scope="col" class="py-2 text-end font-medium">{{ $passed }}</th>
                    <th scope="col" class="py-2 text-end font-medium">{{ $failed }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($config['days'] as $day)
                    <tr>
                        <th scope="row" class="py-2 text-start font-medium">
                            <a href="{{ $day['url'] }}" class="text-brand-700 underline-offset-4 hover:underline focus-visible:outline-brand-500 dark:text-brand-300">
                                <time datetime="{{ $day['date'] }}">{{ $day['label'] }}</time>
                            </a>
                        </th>
                        <td class="py-2 text-end tabular-nums">{{ Number::format($day['passed'], locale: app()->getLocale()) }}</td>
                        <td class="py-2 text-end tabular-nums">{{ Number::format($day['failed'], locale: app()->getLocale()) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </details>
</x-common.component-card>
