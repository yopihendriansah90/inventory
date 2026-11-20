@extends('layouts.app')

@section('content')
    <div class="space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-2">
            <x-common.page-breadcrumb :pageTitle="__('inventory.dashboard.'.$role.'.title')" :show-trail="false" class="mb-0! pt-1" />
            @switch($role)
                @case('qc')
                    <x-ui.button as="a" :href="route('inventory.qc', ['new' => 1]).'#inspection'">{{ __('inventory.dashboard.qc.create') }}</x-ui.button>
                    @break
                @case('warehouse')
                    <x-ui.button as="a" :href="route('inventory.receiving.scan')">
                        <x-svg.qr-scan class="size-5" aria-hidden="true" />
                        {{ __('inventory.warehouse.scan') }}
                    </x-ui.button>
                    @break
                @case('cs')
                    <x-ui.button as="a" :href="route('inventory.dispatch.create')">{{ __('inventory.dispatch.create') }}</x-ui.button>
            @endswitch
        </div>

        <ul @class(['grid grid-cols-2 gap-3 sm:gap-4', 'sm:grid-cols-4' => $role === 'warehouse', 'sm:grid-cols-3' => $role !== 'warehouse'])>
            @foreach ($metrics as [$label, $value, $url])
                <li @class(['col-span-2 sm:col-span-1' => $role !== 'warehouse' && $loop->first])>
                    <a href="{{ $url }}" class="flex h-full flex-col gap-2 rounded-2xl border border-gray-200 bg-white p-4 hover:border-brand-400 focus-visible:outline-brand-500 dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-brand-500 sm:p-5">
                        <span class="flex items-center justify-between gap-1 text-sm font-medium text-gray-600 dark:text-gray-300">
                            {{ __($label) }}
                            <x-svg.chevron-right class="size-4 shrink-0 text-gray-400 rtl:rotate-180 dark:text-gray-400" aria-hidden="true" />
                        </span>
                        <span class="mt-auto text-2xl font-semibold tabular-nums text-gray-900 dark:text-white">{{ Number::format($value, locale: app()->getLocale()) }}</span>
                    </a>
                </li>
            @endforeach
        </ul>

        @switch($role)
            @case('qc')
                <x-main.inventory.dashboard-receipts :tickets="$receipts" />
                @break
            @case('warehouse')
                <div class="grid items-start gap-5 lg:grid-cols-2">
                    <x-main.inventory.dashboard-receipts :tickets="$receipts" warehouse />
                    <x-main.inventory.dashboard-dispatches :tickets="$dispatches" warehouse />
                </div>
                @break
            @case('cs')
                <x-common.component-card :title="__('inventory.stock.card')">
                    <x-main.inventory.stock-browser :variants="$variants" preview />
                    <x-ui.button as="a" :href="route('inventory.stock')" variant="outline" size="sm">{{ __('inventory.dashboard.all_stock') }}</x-ui.button>
                </x-common.component-card>
                <x-main.inventory.dashboard-dispatches :tickets="$dispatches" />
        @endswitch
    </div>
@endsection
