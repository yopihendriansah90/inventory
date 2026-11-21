@extends('layouts.app')

@section('content')
    @php($heading = auth()->user()->can('request-dispatch') ? 'inventory.dispatch.tracking' : 'inventory.dispatch.title')
    <x-common.page-breadcrumb :pageTitle="__($heading)" class="max-sm:[&_nav]:hidden" />

    <x-common.component-card :title="__('inventory.dispatch.list')">
        <div x-data="stockSearch(@js($search), 'X-Dispatch-Results')">
            @if ($status)
                <div class="mb-4 flex flex-wrap items-center gap-3">
                    <x-main.inventory.dispatch-status :status="$status" />
                    <x-ui.button as="a" :href="route('inventory.dispatch.index')" variant="outline" size="sm">{{ __('inventory.dashboard.clear_filter') }}</x-ui.button>
                </div>
            @endif
            <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
                <form method="GET" action="{{ route('inventory.dispatch.index') }}" x-ref="form" x-on:submit.prevent="search()"
                    data-search-error="{{ __('inventory.search.error') }}" class="grid w-full grid-cols-2 items-end gap-2 sm:flex sm:w-auto sm:flex-wrap sm:gap-3">
                    @if ($status)
                        <input type="hidden" name="status" value="{{ $status }}">
                    @endif
                    <div class="col-span-2 min-w-0 sm:min-w-56 sm:grow">
                        <label for="dispatch-search" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('inventory.dispatch.search') }}
                        </label>
                        <x-main.inventory.input id="dispatch-search" type="search" name="q" x-ref="input" x-on:input="schedule()"
                            :value="$search" :placeholder="__('inventory.dispatch.search_placeholder')" maxlength="100" />
                        <x-main.inventory.field-error name="q" />
                    </div>
                    <x-ui.button type="submit" variant="outline" :class="auth()->user()->can('request-dispatch') ? 'h-11 max-sm:col-span-2' : 'h-11'">{{ __('inventory.search.action') }}</x-ui.button>
                    <x-ui.button type="button" variant="ghost" x-cloak x-show="hasQuery" x-on:click="clear()" class="max-sm:order-last max-sm:col-span-2">
                        {{ __('inventory.search.clear') }}
                    </x-ui.button>
                    @can('receive-stock')
                        <x-ui.button as="a" :href="route('inventory.dispatch.scan')" class="h-11 max-sm:whitespace-normal">
                            <x-svg.qr-scan class="size-5" aria-hidden="true" />
                            {{ __('inventory.warehouse.scan') }}
                        </x-ui.button>
                    @endcan
                </form>
                @can('request-dispatch')
                    <x-ui.button as="a" :href="route('inventory.dispatch.create')">{{ __('inventory.dispatch.create') }}</x-ui.button>
                @endcan
            </div>

            <p x-cloak x-show="loading" role="status" class="mb-3 text-sm text-gray-600 dark:text-gray-300">{{ __('inventory.search.loading') }}</p>
            <div x-ref="results" aria-live="polite" x-bind:aria-busy="loading.toString()">
                <x-main.inventory.dispatch-table :tickets="$tickets" />
            </div>
        </div>
    </x-common.component-card>
@endsection
