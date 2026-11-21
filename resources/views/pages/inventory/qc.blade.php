@extends('layouts.app')

@if (request()->boolean('new') || $selectedSku !== null || $errors->any() || $errors->variant->any())
    @section('inventory-draft', 'true')
@endif

@section('content')
    <x-common.page-breadcrumb :pageTitle="__('inventory.qc.title')" class="max-sm:[&_nav]:hidden" />

    <div class="space-y-6">
        @hasSection('inventory-draft')
            <x-ui.button as="a" :href="route('inventory.qc')" variant="outline">{{ __('inventory.ticket.back_to_list') }}</x-ui.button>
            <x-common.component-card id="inspection" class="scroll-mt-28" :title="__('inventory.qc.form')">
                <x-main.inventory.qc-form :variants="$variants" :vendors="$vendors" :selected-sku="$selectedSku" />
            </x-common.component-card>
        @else
            <x-ui.button as="a" :href="route('inventory.qc', ['new' => 1]).'#inspection'" class="w-full sm:w-auto">{{ __('inventory.qc.form') }}</x-ui.button>

            <x-common.component-card :title="__('inventory.qc.card')">
                @if ($date || $result)
                    <div class="flex flex-wrap items-center gap-3">
                        @if ($date)
                            <x-ui.badge color="gray">{{ \Illuminate\Support\Carbon::parse($date)->locale(app()->getLocale())->translatedFormat('d M Y') }}</x-ui.badge>
                        @endif
                        @if ($result)
                            <x-ui.badge color="gray">{{ __('inventory.dashboard.qc.'.$result) }}</x-ui.badge>
                        @endif
                        <x-ui.button as="a" :href="route('inventory.qc')" variant="outline" size="sm">{{ __('inventory.dashboard.clear_filter') }}</x-ui.button>
                    </div>
                @endif
                <div x-data="stockSearch(@js($search), 'X-QC-Ticket-Results')">
                    <div class="mb-4">
                        <form method="GET" action="{{ route('inventory.qc') }}" x-ref="form" x-on:submit.prevent="search()"
                            data-search-error="{{ __('inventory.qc.search_error') }}" class="grid grid-cols-2 items-end gap-2 sm:flex sm:flex-wrap sm:gap-3">
                            @foreach (['date' => $date, 'result' => $result] as $filter => $value)
                                @if ($value !== null)
                                    <input type="hidden" name="{{ $filter }}" value="{{ $value }}">
                                @endif
                            @endforeach
                            <div class="col-span-2 w-full sm:min-w-56 sm:flex-1">
                                <label for="qc-ticket-search" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('inventory.qc.search') }}</label>
                                <div class="relative">
                                    <x-main.inventory.input id="qc-ticket-search" type="search" name="q" :value="$search" :placeholder="__('inventory.qc.search_placeholder')"
                                        class="pe-11 [&::-webkit-search-cancel-button]:hidden sm:pe-3"
                                        maxlength="100" x-ref="input" x-on:input="schedule()" />
                                    <x-ui.button x-cloak="x-cloak" x-show="hasQuery" type="button" variant="ghost" size="icon-sm"
                                        class="absolute inset-y-0 end-1 my-auto sm:hidden" :aria-label="__('inventory.search.clear')" x-on:click="clear()">
                                        <span class="text-lg leading-none" aria-hidden="true">&times;</span>
                                    </x-ui.button>
                                </div>
                                <x-main.inventory.field-error name="q" bag="qcFilters" />
                            </div>
                            <x-ui.button type="submit" class="h-11">{{ __('inventory.search.action') }}</x-ui.button>
                            <x-ui.button x-cloak="x-cloak" x-show="hasQuery" type="button" variant="outline" class="hidden h-11 sm:inline-flex" x-on:click="clear()">{{ __('inventory.search.clear') }}</x-ui.button>
                            <x-ui.button as="a" :href="route('inventory.qc.scan')" variant="outline" class="h-11">
                                <x-svg.qr-scan class="size-5" aria-hidden="true" />
                                {{ __('inventory.warehouse.scan') }}
                            </x-ui.button>
                        </form>
                    </div>
                    <p x-cloak x-show="loading" role="status" class="mb-3 text-sm text-gray-600 dark:text-gray-300">{{ __('inventory.search.loading') }}</p>
                    <div x-ref="results" aria-live="polite" x-bind:aria-busy="loading.toString()">
                        <x-main.inventory.tickets-table :tickets="$tickets" />
                    </div>
                </div>
            </x-common.component-card>
        @endif
    </div>
@endsection
